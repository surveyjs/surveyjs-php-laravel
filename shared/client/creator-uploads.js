// Upload Files in Creator
import { mountSurvey, note, show } from "./host.js";
import { Model } from "survey-core";
import { SurveyCreator, renderSurveyCreator } from "survey-creator-js";
import { createSchemaSaver, responseError } from "./schema-saver.js";

const formId = "support-assets";
const preview = new URLSearchParams(location.search).has("form");
const creator = new SurveyCreator();
const element = document.getElementById("creator");
const retryLoad = document.getElementById("retry-load");
const retrySave = document.getElementById("retry-save");
const openSaved = document.getElementById("open-saved-form");
let loaded = false;
let uploads = 0;
let busy = false;

const saver = createSchemaSaver(() => creator.JSON, async json => {
  const response = await fetch(`/api/forms/${formId}`, { method: "PUT", headers: { "Content-Type": "application/json" }, body: json });
  if (!response.ok) throw await responseError(response, "Couldn't save the form schema. Please try again.");
}, ({ state, error }) => {
  if (!loaded || preview) return;
  show("schema-status", state === "saved" ? "Form schema saved." : state === "saving" ? "Saving the form schema..."
    : state === "error" ? `${error} Uploaded files are retained; retry saving their URLs.` : "The form schema has unsaved changes.");
  retrySave.hidden = state !== "error";
  if (state === "error") creator.notify(error, "error");
});
creator.saveSurveyFunc = (saveNo, callback) => saver.save().then(saved => callback(saveNo, saved));
creator.onModified.add(() => { if (loaded) saver.changed(); });

// #region sjs:creator-uploads.client
// Store each uploaded image separately and keep its URL in the form schema
creator.onUploadFile.add(async (_, options) => {
  const label = options.propertyName === "logo" || options.elementType === "header" ? "Logo" : "Image";
  uploads++;
  updateControls();
  show("upload-status", `Uploading ${label.toLowerCase()}...`);
  try {
    if (!options.files.length || options.files.some(file => file.size > 5242880 || !["image/png", "image/jpeg", "image/webp", "image/gif"].includes(file.type))) throw new Error("Choose a PNG, JPEG, WebP, or GIF image up to 5 MB.");
    const body = new FormData();
    options.files.forEach(file => body.append("files[]", file));
    const response = await fetch("/api/files", { method: "POST", body });
    const ids = response.ok ? await response.json() : null;
    if (!Array.isArray(ids) || ids.length !== options.files.length || ids.some(id => typeof id !== "string" || !id)) throw new Error(`Couldn't upload the ${label.toLowerCase()}. Please try again.`);
    options.callback("success", `/api/files/${encodeURIComponent(ids[0])}`);
    show("upload-status", `${label} uploaded. Its URL is shown below; check the schema save status.`);
    note(`${label} uploaded. The form schema now references its URL.`, "ok");
    showAssetUrls();
  } catch (error) {
    options.callback("error");
    show("upload-status", error.message);
    note(error.message, "error");
  } finally { uploads--; updateControls(); }
});
// #endregion

function updateControls() {
  if (openSaved) openSaved.setAttribute("aria-disabled", String(!loaded || busy || uploads > 0));
  retrySave.disabled = busy || uploads > 0;
}
function showAssetUrls() {
  const urls = [];
  if (creator.JSON.logo) urls.push(`Logo: ${creator.JSON.logo}`);
  for (const question of creator.survey.getAllQuestions()) if (question.imageLink) urls.push(`${question.name}: ${question.imageLink}`);
  show("asset-urls", urls.join("\n"));
}
async function loadDefinition() {
  retryLoad.hidden = true;
  show("schema-status", "Loading the saved form...");
  try {
    const response = await fetch(`/api/forms/${formId}`);
    if (!response.ok) throw await responseError(response, "Couldn't load the form schema. Please try again.");
    const { definition } = await response.json();
    if (!definition || typeof definition !== "object" || Array.isArray(definition)) throw new Error("The server returned an invalid form schema.");
    creator.JSON = definition;
    loaded = true;
    saver.markSaved();
    if (preview) { mountSurvey(new Model(definition), "creator"); show("schema-status", "This preview uses the saved image URLs."); }
    else { renderSurveyCreator(creator, element); creator.autoSaveEnabled = true; }
    element.hidden = false;
    element.inert = false;
    showAssetUrls();
  } catch (error) { show("schema-status", error.message); retryLoad.hidden = false; }
  updateControls();
}
retryLoad.addEventListener("click", loadDefinition);
retrySave.addEventListener("click", () => creator.saveSurvey());
openSaved?.addEventListener("click", async event => {
  event.preventDefault();
  if (!loaded || busy || uploads) return;
  const tab = window.open("", "_blank");
  if (!tab) { show("schema-status", "Allow pop-ups to open the saved form."); return; }
  busy = true;
  element.inert = true;
  updateControls();
  creator.saveSurvey();
  if (await saver.save()) tab.location.href = new URL("?form", location.href).href;
  else tab.close();
  element.inert = false;
  busy = false;
  updateControls();
});
await loadDefinition();
