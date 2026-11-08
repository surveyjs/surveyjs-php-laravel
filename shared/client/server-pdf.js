// Generate PDFs
import { note, setStoredQuery } from "./host.js";
import { responseError } from "./schema-saver.js";

const urls = new Set();

// #region sjs:server-pdf.client
// Open a tab during the click and navigate only after the server returns a PDF
async function generatePdf(id, action, row) {
  const tab = action === "open" ? window.open("", "_blank") : null;
  if (tab) tab.document.body.textContent = "Generating the PDF...";
  try {
    const response = await fetch(`/api/claims/${id}/pdf`);
    if (!response.ok) throw await responseError(response, "Couldn't generate the PDF. Please try again.");
    if (!response.headers.get("content-type")?.startsWith("application/pdf")) throw new Error("The server didn't return a PDF. Please try again.");
    const blob = await response.blob();
    if (!blob.size) throw new Error("The server returned an empty PDF. Please try again.");
    const url = URL.createObjectURL(blob);
    urls.add(url);
    for (const link of row.querySelectorAll("[data-pdf-link]")) link.href = url;
    row.querySelector("[data-pdf-links]").hidden = false;
    if (action === "download") row.querySelector('[data-pdf-link="download"]').click();
    else if (tab && !tab.closed) tab.location = url;
    return action === "download" ? "PDF ready. Download started." : tab && !tab.closed
      ? "PDF opened in a new tab." : "PDF ready. Use the links below to open or download it.";
  } catch (error) { tab?.close(); throw error; }
}
// #endregion

for (const button of document.querySelectorAll("[data-claim]")) {
  button.addEventListener("click", async () => {
    const row = button.closest("[data-claim-row]");
    const status = row.querySelector("[data-pdf-status]");
    const buttons = row.querySelectorAll("[data-claim]");
    for (const item of buttons) item.disabled = true;
    row.querySelector("[data-pdf-links]").hidden = true;
    status.textContent = "Generating the PDF...";
    row.setAttribute("aria-busy", "true");
    setStoredQuery({ id: button.dataset.claim });
    try {
      status.textContent = await generatePdf(button.dataset.claim, button.dataset.action, row);
      note(`Claim #${button.dataset.claim}: ${status.textContent}`, "ok");
    } catch (error) {
      status.textContent = error instanceof TypeError ? "Couldn't generate the PDF. Please try again." : error.message;
      note(`Claim #${button.dataset.claim}: ${status.textContent}`, "error");
    } finally {
      for (const item of buttons) item.disabled = false;
      row.setAttribute("aria-busy", "false");
    }
  });
}
window.addEventListener("pagehide", () => { for (const url of urls) URL.revokeObjectURL(url); });
