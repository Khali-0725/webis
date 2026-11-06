const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { createContext, runInContext } = require('node:vm');
const { createHmac } = require('node:crypto');

const source = readFileSync(resolve(__dirname, '../../docs/google-apps-script/Code.gs'), 'utf8');
const secret = 'a'.repeat(64);

function relay({ quota = 100, sendFails = false, configured = true } = {}) {
    const properties = configured ? { WEBIS_MAIL_SECRET: secret } : {};
    const sent = [];
    const context = createContext({
        Date, JSON,
        PropertiesService: { getScriptProperties: () => ({
            getProperty: key => properties[key] ?? null,
            getProperties: () => ({ ...properties }),
            setProperty: (key, value) => { properties[key] = value; },
            deleteProperty: key => { delete properties[key]; },
        }) },
        Utilities: {
            Charset: { UTF_8: 'UTF-8' },
            computeHmacSha256Signature: (value, key) => [...createHmac('sha256', key).update(value).digest()]
                .map(byte => byte > 127 ? byte - 256 : byte),
        },
        LockService: { getScriptLock: () => ({ tryLock: () => true, releaseLock() {} }) },
        MailApp: {
            getRemainingDailyQuota: () => quota,
            sendEmail: message => {
                if (sendFails) throw Error('Private upstream error');
                sent.push(JSON.parse(JSON.stringify(message)));
            },
        },
        ContentService: {
            MimeType: { JSON: 'application/json' },
            createTextOutput: text => ({ setMimeType: () => JSON.parse(text) }),
        },
    });
    runInContext(source, context);
    return { context, sent };
}

function request(overrides = {}, signingKey = secret) {
    const payload = JSON.stringify({
        timestamp: Math.floor(Date.now() / 1000), id: 'b'.repeat(32),
        to: 'recipient@gmail.com', subject: 'Your WEBIS password reset code',
        text: 'Code: 123456', html: '<p>Code: <b>123456</b></p>', name: 'WEBIS',
        ...overrides,
    });
    return { postData: { contents: JSON.stringify({
        payload, signature: createHmac('sha256', signingKey).update(payload).digest('hex'),
    }) } };
}

test('signed request sends text and HTML once, including on duplicate delivery', () => {
    const { context, sent } = relay();
    const event = request();
    assert.equal(context.doPost(event).ok, true);
    assert.equal(context.doPost(event).ok, true);
    assert.equal(sent.length, 1);
    assert.deepEqual(sent[0], {
        to: 'recipient@gmail.com', subject: 'Your WEBIS password reset code',
        body: 'Code: 123456', htmlBody: '<p>Code: <b>123456</b></p>', name: 'WEBIS',
    });
});

test('invalid signature, missing secret, expired or future requests cannot send', () => {
    for (const event of [
        request({}, 'wrong-secret'),
        request({ timestamp: Math.floor(Date.now() / 1000) - 301 }),
        request({ timestamp: Math.floor(Date.now() / 1000) + 60 }),
    ]) {
        const { context, sent } = relay();
        assert.equal(context.doPost(event).ok, false);
        assert.equal(sent.length, 0);
    }
    const { context, sent } = relay({ configured: false });
    assert.equal(context.doPost(request()).ok, false);
    assert.equal(sent.length, 0);
});

test('tampered contents, malformed JSON and multiple recipients cannot send', () => {
    const tampered = request();
    tampered.postData.contents = tampered.postData.contents.replace('123456', '654321');
    for (const event of [tampered, {}, { postData: { contents: 'invalid' } }, request({ to: 'one@gmail.com,two@gmail.com' })]) {
        const { context, sent } = relay();
        assert.equal(context.doPost(event).ok, false);
        assert.equal(sent.length, 0);
    }
});

test('quota exhaustion and send errors never report success or expose upstream errors', () => {
    for (const options of [{ quota: 0 }, { sendFails: true }]) {
        const { context, sent } = relay(options);
        const event = request();
        const result = context.doPost(event);
        assert.equal(result.ok, false);
        assert.equal(sent.length, 0);
        assert.equal(JSON.stringify(result).includes('Private upstream error'), false);
        if (options.sendFails) {
            assert.equal(context.doPost(event).error, 'delivery_uncertain');
        }
    }
});
