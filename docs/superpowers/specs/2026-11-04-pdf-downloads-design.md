# Direct PDF downloads

The professor wants the existing Print actions replaced by downloads. The provider Earnings page and client Payment Receipt page are the only pages with Print buttons. The receipt also has a text download; one PDF action will replace both receipt actions.

## Approach

Use a small shared browser PDF generator with jsPDF. Each page passes its already loaded data to the generator and starts a `.pdf` download. The provider report includes the selected date range, summary amounts, and confirmed transactions. The receipt includes its status and the existing receipt fields. Currency is written as `PHP` for reliable rendering in the built-in PDF font. File names use the receipt ID or a fixed earnings report prefix.

Other options considered: browser print-to-PDF still requires a print dialog; a backend PDF endpoint would add API and server maintenance for data already present in the browser. Direct client download matches the requested interaction.

## Constraints

- No Print button or text download remains on these two pages.
- Keep the existing on-screen content and data requests.
- PDFs must render readable tables, long values, and multiple pages.
- Download uses current filtered earnings data; unavailable transactions disable that download.
