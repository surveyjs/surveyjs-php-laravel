// Upload Files
import { mountCreator, note } from "./host.js";
import { SurveyCreator } from "survey-creator-js";

const formId = "support";

// Load and save as in creator-load-save; saving automatically, so the stored panel shows each change
const creator = new SurveyCreator({ showTranslationTab: true });
creator.JSON = (await fetch(`/api/forms/${formId}`).then(r => r.json())).definition;
creator.autoSaveEnabled = true;
creator.saveSurveyFunc = async (saveNo, callback) => {
  const res = await fetch(`/api/forms/${formId}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(creator.JSON)
  }).catch(() => null);
  callback(saveNo, Boolean(res?.ok));
  if (!res?.ok) {
    const report = res ? await res.json().catch(() => null) : null;
    creator.notify(report?.error ?? "Couldn't save the form schema. Please try again.", "error");
  }
};

// #region sjs:creator-uploads.client
// Images and logos added in Creator: store them, keep only the URL in the definition
creator.onUploadFile.add(async (_, options) => {
  const body = new FormData();
  options.files.forEach(file => body.append("files[]", file));   // POST /api/files; "files[]" for PHP
  const response = await fetch("/api/files", { method: "POST", body }).catch(() => null);
  const ids = response?.ok ? await response.json().catch(() => null) : null;
  if (!Array.isArray(ids) || ids.length !== options.files.length || typeof ids[0] !== "string" || !ids[0]) return options.callback("error");
  options.callback("success", `/api/files/${encodeURIComponent(ids[0])}`);   // the definition keeps a URL
});
// #endregion

mountCreator(creator);

// Demo only
creator.onUploadFile.add((_, options) => {
  const callback = options.callback;
  const label = options.propertyName === "logo" || options.elementType === "header" ? "Logo" : "File";
  options.callback = (status, url) => {
    callback(status, url);
    note(status === "success"
      ? `${label} uploaded. The form schema now references its URL.`
      : `Couldn't upload the ${label.toLowerCase()}. Please try again.`, status === "success" ? "ok" : "error");
  };
});
