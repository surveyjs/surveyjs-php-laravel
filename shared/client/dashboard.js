// Analyze Results with Dashboard
import { setStoredQuery, show } from "./host.js";
import { Model } from "survey-core";
import { Dashboard } from "survey-analytics";

const fromInput = document.getElementById("from");
const toInput = document.getElementById("to");
const periodInput = document.getElementById("period");
const container = document.getElementById("dashboard");
const retry = document.getElementById("retry-dashboard");
let dashboard, schemaPromise, controller;
let revision = 0;

async function loadSchema() {
  schemaPromise ??= fetch("/api/forms/feedback").then(async response => {
    if (!response.ok) throw new Error("Schema load failed");
    const { definition } = await response.json();
    if (!definition || typeof definition !== "object" || Array.isArray(definition)) throw new Error("Invalid schema");
    return definition;
  }).catch(error => { schemaPromise = null; throw error; });
  return schemaPromise;
}

// #region sjs:dashboard.client
// Load the selected date range and update one Dashboard instance
async function loadResults(from, to, signal) {
  const query = new URLSearchParams({ formId: "feedback", from, to });
  const [definition, response] = await Promise.all([
    loadSchema(), fetch(`/api/responses?${query}`, { signal })
  ]);
  if (!response.ok) throw new Error("Responses load failed");
  const data = await response.json();
  if (!Array.isArray(data)) throw new Error("Invalid responses");
  return { definition, data };
}

function renderResults(definition, data) {
  if (!dashboard) {
    const survey = new Model(definition);
    dashboard = new Dashboard({ questions: survey.getAllQuestions(), data });
    dashboard.render(container);
  } else {
    dashboard.resetFilter();
    dashboard.updateData(data);
  }
}
// #endregion

async function refresh() {
  const run = ++revision;
  controller?.abort();
  controller = new AbortController();
  container.hidden = true;
  container.setAttribute("aria-busy", "true");
  retry.hidden = true;
  show("dashboard-range", `${fromInput.value || "First response"} to ${toInput.value || "Latest response"} (UTC)`);
  if (fromInput.value && toInput.value && fromInput.value > toInput.value) {
    show("dashboard-status", "Choose an end date on or after the start date.");
    container.setAttribute("aria-busy", "false");
    return;
  }
  show("dashboard-status", "Loading responses...");
  try {
    const { definition, data } = await loadResults(fromInput.value, toInput.value, controller.signal);
    if (run !== revision) return;
    setStoredQuery({ from: fromInput.value, to: toInput.value });
    show("dashboard-status", data.length ? `${data.length} ${data.length === 1 ? "response" : "responses"} loaded` : "No responses");
    if (data.length) {
      container.hidden = false;
      renderResults(definition, data);
    }
  } catch (error) {
    if (run !== revision || error.name === "AbortError") return;
    show("dashboard-status", "Couldn't load the responses. Please try again.");
    retry.hidden = false;
  } finally {
    if (run === revision) container.setAttribute("aria-busy", "false");
  }
}

function selectPeriod() {
  const today = new Date();
  toInput.value = today.toISOString().slice(0, 10);
  if (periodInput.value === "all") fromInput.value = "";
  else {
    today.setUTCDate(today.getUTCDate() - Number(periodInput.value) + 1);
    fromInput.value = today.toISOString().slice(0, 10);
  }
  refresh();
}
periodInput.addEventListener("change", () => { if (periodInput.value !== "custom") selectPeriod(); });
for (const input of [fromInput, toInput]) input.addEventListener("change", () => { periodInput.value = "custom"; refresh(); });
retry.addEventListener("click", refresh);
addEventListener("pagehide", () => { controller?.abort(); dashboard?.destroy(); }, { once: true });
selectPeriod();
