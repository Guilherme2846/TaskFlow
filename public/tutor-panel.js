const panel = document.querySelector('#tutor-panel');
const launch = document.querySelector('#tutor-launch');
const closePanel = document.querySelector('#tutor-close');
const picker = document.querySelector('#panel-task');
const frame = document.querySelector('#panel-chat');
function showTask() {
  const url = '?page=tutor&panel=1&id=' + encodeURIComponent(picker.value);
  if (frame.getAttribute('src') !== url) frame.src = url;
}
const alertsButton = document.querySelector('#alert-launch');
const alertsView = document.querySelector('#panel-alerts');
const chatView = document.querySelector('#panel-conversation');
let activeButton = launch;
function toggleTutor(open, button = launch) {
  const returnFocus = activeButton;
  if (open) activeButton = button;
  const alertsOpen = open && button === alertsButton;
  panel.hidden = !open;
  if (alertsView) alertsView.hidden = !alertsOpen;
  chatView.hidden = alertsOpen;
  launch.setAttribute('aria-expanded', String(open && !alertsOpen));
  alertsButton?.setAttribute('aria-expanded', String(alertsOpen));
  if (open && !alertsOpen && picker) showTask();
  (open ? closePanel : returnFocus).focus();
}
for (const button of [launch, alertsButton].filter(Boolean)) {
  button.addEventListener('click', () => toggleTutor(panel.hidden || activeButton !== button, button));
}
closePanel.addEventListener('click', () => toggleTutor(false));
picker?.addEventListener('change', showTask);
frame?.addEventListener('load', () => {
  frame.contentDocument?.addEventListener('keydown', event => {
    if (event.key === 'Escape') toggleTutor(false);
  });
});
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && !panel.hidden) toggleTutor(false);
});
document.addEventListener('click', event => {
  const link = event.target.closest('a[href^="?page=tutor&"]');
  if (!link || !picker || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
  const taskId = new URL(link.href).searchParams.get('id');
  if (!Array.from(picker.options).some(option => option.value === taskId)) return;
  event.preventDefault();
  picker.value = taskId;
  toggleTutor(true);
});
