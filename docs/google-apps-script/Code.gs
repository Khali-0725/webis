/**
 * WEBIS Gmail relay. Deploy as a web app: execute as Me, access Anyone.
 * Set WEBIS_MAIL_SECRET in Project Settings > Script properties (32+ characters).
 * Only signed backend requests can send mail. Never place the secret in frontend code.
 */
function doPost(e) {
  var lock = null;
  try {
    var properties = PropertiesService.getScriptProperties();
    var secret = properties.getProperty('WEBIS_MAIL_SECRET');
    if (!secret || secret.length < 32) return jsonResult_({ ok: false, error: 'unauthorized' });
    var envelope = JSON.parse(e.postData.contents);
    if (typeof envelope.payload !== 'string' || envelope.payload.length > 100000
        || typeof envelope.signature !== 'string' || !/^[a-f0-9]{64}$/.test(envelope.signature)) {
      return jsonResult_({ ok: false, error: 'invalid_request' });
    }
    var bytes = Utilities.computeHmacSha256Signature(envelope.payload, secret, Utilities.Charset.UTF_8);
    var signature = bytes.map(function (byte) {
      return ('0' + ((byte + 256) % 256).toString(16)).slice(-2);
    }).join('');
    var mismatch = 0;
    for (var i = 0; i < signature.length; i++) {
      mismatch |= signature.charCodeAt(i) ^ envelope.signature.charCodeAt(i);
    }
    if (mismatch) return jsonResult_({ ok: false, error: 'unauthorized' });

    var message = JSON.parse(envelope.payload);
    var now = Math.floor(Date.now() / 1000);
    if (!Number.isInteger(message.timestamp) || now - message.timestamp > 300 || message.timestamp - now > 30
        || typeof message.id !== 'string' || !/^[a-f0-9]{32}$/.test(message.id)
        || typeof message.to !== 'string' || !/^[^\s@,;<>]+@[^\s@,;<>]+\.[^\s@,;<>]+$/.test(message.to)
        || typeof message.subject !== 'string' || !message.subject || message.subject.length > 998
        || /[\r\n]/.test(message.subject) || typeof message.text !== 'string'
        || (message.html != null && typeof message.html !== 'string')
        || typeof message.name !== 'string' || message.name.length > 100 || /[\r\n]/.test(message.name)) {
      return jsonResult_({ ok: false, error: 'invalid_request' });
    }

    lock = LockService.getScriptLock();
    if (!lock.tryLock(5000)) return jsonResult_({ ok: false, error: 'busy' });
    // Persist IDs through the signature validity window to prevent duplicate sends and replays.
    var stored = properties.getProperties();
    Object.keys(stored).forEach(function (key) {
      if (key.indexOf('sent_') === 0 && Number(stored[key].split(':')[0]) < now - 360) {
        properties.deleteProperty(key);
      }
    });
    var key = 'sent_' + message.id;
    var previous = properties.getProperty(key);
    if (previous) {
      return jsonResult_(previous.split(':')[1] === 'done'
        ? { ok: true } : { ok: false, error: 'delivery_uncertain' });
    }
    if (MailApp.getRemainingDailyQuota() < 1) return jsonResult_({ ok: false, error: 'quota_exceeded' });

    var options = { to: message.to, subject: message.subject, body: message.text, name: message.name };
    if (message.html) options.htmlBody = message.html;
    properties.setProperty(key, now + ':pending');
    MailApp.sendEmail(options);
    properties.setProperty(key, now + ':done');
    return jsonResult_({ ok: true });
  } catch (error) {
    // Do not return or log OTPs, recipient addresses, signatures or upstream error details.
    return jsonResult_({ ok: false, error: 'delivery_failed' });
  } finally {
    if (lock) lock.releaseLock();
  }
}

function jsonResult_(result) {
  return ContentService.createTextOutput(JSON.stringify(result)).setMimeType(ContentService.MimeType.JSON);
}

/** Run once in the editor to grant permission to send mail and see the remaining daily quota. */
function authorizeMail() {
  console.log('Remaining email recipients today: ' + MailApp.getRemainingDailyQuota());
}
