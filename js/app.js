'use strict';

// Presentation-only status labels. Stored values and submitted fields are untouched.
document.querySelectorAll('.status').forEach(badge => {
  const stages = { 'New': ['new', 'New request'], 'Assigned': ['assigned', 'Assigned'],
    'On the Way for Collection': ['onway', 'On the way'], 'Sample Collected': ['collected', 'Sample collected'],
    'Sent to Lab': ['lab', 'Sent to lab'], 'Delivered': ['delivered', 'Delivered'] };
  const stage = stages[badge.textContent.trim()];
  if (stage) { badge.dataset.stage = stage[0]; badge.textContent = stage[1]; }
});

const confirmation = document.getElementById('ui-confirm-modal');
const confirmedForms = new WeakSet();
let pendingConfirmation = null;
document.querySelectorAll('form[data-confirm]').forEach(form => {
  form.addEventListener('submit', event => {
    if (confirmedForms.has(form)) return;
    if (!confirmation || !window.jQuery || !window.jQuery.fn.modal || !form.requestSubmit) {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
      return;
    }
    event.preventDefault();
    pendingConfirmation = { form, submitter: event.submitter };
    document.getElementById('ui-confirm-description').textContent = form.dataset.confirm;
    window.jQuery(confirmation).modal('show');
  });
});
if (confirmation && window.jQuery) {
  window.jQuery(confirmation).on('shown.bs.modal', () => document.getElementById('ui-confirm-cancel').focus());
  window.jQuery(confirmation).on('hidden.bs.modal', () => {
    if (pendingConfirmation) pendingConfirmation.submitter?.focus();
    pendingConfirmation = null;
  });
  document.getElementById('ui-confirm-accept').addEventListener('click', () => {
    if (!pendingConfirmation) return;
    const { form, submitter } = pendingConfirmation;
    confirmedForms.add(form);
    window.jQuery(confirmation).modal('hide');
    try { submitter ? form.requestSubmit(submitter) : form.requestSubmit(); }
    finally { confirmedForms.delete(form); }
  });
}
document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));

// Preserve server-rendered tables and expose their horizontal scroll to keyboard users.
document.querySelectorAll('.table-responsive').forEach(region => {
  region.tabIndex = 0;
  region.setAttribute('role', 'region');
  region.setAttribute('aria-label', 'Records table. Scroll horizontally to view all columns.');
  region.querySelectorAll('thead th').forEach(th => th.setAttribute('scope', 'col'));
});
document.querySelectorAll('.form-control[required]').forEach(control => control.setAttribute('aria-required', 'true'));
