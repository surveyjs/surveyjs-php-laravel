// Store Files Separately
import { mountSurvey, note, onRequest, page, show } from "./host.js";
import { Model } from "survey-core";

// In the definition: { "type": "file", "name": "receipt", "storeDataAsText": false, "allowMultiple": true }
const survey = new Model(page.definition);

// #region sjs:store-files.client
// Upload: your server stores the files and returns an id (or a URL) for each
survey.onUploadFiles.add(async (_, options) => {
  const body = new FormData();
  options.files.forEach(file => body.append("files[]", file));   // "files[]": PHP keeps only the last of several "files"
  const response = await fetch("/api/files", { method: "POST", body }).catch(() => null);
  const ids = response?.ok ? await response.json().catch(() => null) : null;
  if (!Array.isArray(ids) || ids.length !== options.files.length || ids.some(id => typeof id !== "string" || !id)) {
    return options.callback([], ["Couldn't upload files. Please try again."]);
  }
  // The response stores these ids, not the file contents
  options.callback(options.files.map((file, i) => ({ file, content: ids[i] })));
});

// Preview: only when the stored value is not a public URL the browser can open
survey.onDownloadFile.add(async (_, options) => {
  const res = await fetch(`/api/files/${encodeURIComponent(options.content)}`);
  if (!res.ok) return options.callback("error");
  const reader = new FileReader();
  reader.onload = () => options.callback("success", reader.result);   // base64 data the form shows
  reader.readAsDataURL(await res.blob());
});
// #endregion

// Saving the finished response is in save-response
survey.onComplete.add(async (sender) => {
  await fetch("/api/responses", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ formId: "expenses", data: sender.data })
  });
});

mountSurvey(survey);

// Demo only: what the response would weigh with storeDataAsText: true (computed here, never posted)
const size = (bytes) => (bytes > 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(2)} MB` : `${(bytes / 1024).toFixed(1)} KB`);
const asDataUrl = (file) => new Promise((resolve, reject) => {
  const reader = new FileReader();
  reader.onload = () => resolve(reader.result);
  reader.onerror = () => reject(reader.error);
  reader.readAsDataURL(file);
});
const byteSize = (data) => new TextEncoder().encode(JSON.stringify(data)).byteLength;
const uploadedFiles = new Map();
async function reportUpload(data, questionName) {
  const embedded = await Promise.all(data[questionName].map(async (item) => ({
    ...item, content: await asDataUrl(uploadedFiles.get(item.content))
  })));
  const withBase64 = byteSize({ ...data, [questionName]: embedded });
  const withIds = byteSize(data);
  show("sizes", `Response size: ${size(withIds)} with file IDs; ${size(withBase64)} with embedded files.`);
  note(withIds < withBase64
    ? `Files uploaded. Storing file IDs reduced the response size from ${size(withBase64)} to ${size(withIds)}.`
    : "Files uploaded. The response stores file IDs.", "ok");
}
survey.onUploadFiles.add((_, options) => {
  const callback = options.callback;
  options.callback = (files, errors) => {
    callback(files, errors);
    if (errors?.length) {
      show("sizes", errors.join(" "));
      note(errors.join(" "), "error");
    } else if (files?.length) {
      for (const { file, content } of files) uploadedFiles.set(content, file);
      reportUpload(structuredClone(survey.data), options.name).catch(() => {
        show("sizes", "Files uploaded. The response stores file IDs.");
        note("Files uploaded. The response stores file IDs.", "ok");
      });
    }
  };
});
onRequest(({ method, url, status }) => {
  if (method === "GET" && url.startsWith("/api/files/") && status === 404) {
    note("Couldn't load the preview. The file may be missing or inaccessible.", "error");
  }
});
document.getElementById("start-over")?.addEventListener("click", () => {
  survey.clear(true, true);
  uploadedFiles.clear();
  show("sizes", "Upload a file to compare response sizes.");
});
