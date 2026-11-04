# Direct PDF Downloads Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace both WEBIS Print actions with direct PDF downloads.

**Architecture:** A shared browser utility builds receipt and earnings PDF documents from data already fetched by each page. The two pages expose one Download PDF action each.

**Tech Stack:** React, Vitest, jsPDF.

**Spec:** `docs/superpowers/specs/2026-11-04-pdf-downloads-design.md`

## Global Constraints

- No browser print dialog on these pages.
- No backend change.
- Use `PHP` in generated PDFs to avoid missing peso glyphs.

## Review Focus

- Long receipt values and service names wrap within the page.
- Earnings with many transactions continue on a new page.
- Receipt status remains clear when payment is unverified.
- Earnings download uses the selected date range.
- Download waits for transaction data, avoiding incomplete reports.

### Task 1: Shared PDF generation

**Files:** Create `frontend/src/utils/pdfDownloads.js`; test `frontend/tests/pdfDownloads.test.js`; modify `frontend/package.json` and lockfile.

**Interfaces:** `createReceiptPdf(receipt, fields, designation)` and `createEarningsPdf(summary, transactions, from, to)` return jsPDF documents.

- [x] Write tests for valid PDF bytes and relevant receipt/report content, including long and multi-page data.
- [x] Run the tests to see missing implementation failures.
- [x] Add jsPDF and implement the two builders with wrapping and page breaks.
- [x] Run focused tests to green.

### Task 2: Wire both download actions

**Files:** Modify `frontend/src/pages/client/payments/ReceiptPage.jsx` and `frontend/src/pages/provider/earnings/EarningsPage.jsx`; test `frontend/tests/pdfDownloadPages.test.jsx`.

- [x] Write tests that click Download PDF and verify `.pdf` save calls and no Print control.
- [x] Run the tests to see them fail.
- [x] Wire builders to the pages and remove Print and text download actions.
- [x] Run focused tests, lint, and production build.
- [x] Commit, push, and verify deployment.
