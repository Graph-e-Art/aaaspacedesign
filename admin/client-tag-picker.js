const picker = document.querySelector('[data-client-picker]')
const selectedClientInput = document.querySelector('#client-id-value')
const selectedClientLabel = document.querySelector('[data-client-picker-label]')
const pickerError = document.querySelector('[data-client-picker-error]')
const editDialog = document.querySelector('#client-edit-dialog')
const editForm = editDialog?.querySelector('form')
const editIdInput = editForm?.querySelector('[name="client_id"]')
const editNameInput = editForm?.querySelector('[name="client_name"]')

picker?.querySelectorAll('[data-client-select]').forEach((button) => {
  button.addEventListener('click', () => {
    if (selectedClientInput) selectedClientInput.value = button.dataset.clientId || ''
    if (selectedClientLabel) selectedClientLabel.textContent = button.dataset.clientName || 'Select a client'
    if (pickerError) pickerError.hidden = true
    picker.open = false
  })
})

picker?.querySelectorAll('[data-client-edit]').forEach((button) => {
  button.addEventListener('click', () => {
    if (!editDialog || !editIdInput || !editNameInput) return
    editIdInput.value = button.dataset.clientId || ''
    editNameInput.value = button.dataset.clientName || ''
    editDialog.showModal()
    editNameInput.focus()
    editNameInput.select()
  })
})

document.querySelector('#project-entry-form')?.addEventListener('submit', (event) => {
  if (selectedClientInput?.value || selectedClientInput?.dataset.requireClient !== 'true') return
  event.preventDefault()
  if (pickerError) pickerError.hidden = false
  if (picker) picker.open = true
  picker?.querySelector('summary')?.focus()
})

document.addEventListener('click', (event) => {
  if (picker && !picker.contains(event.target)) picker.open = false
})

editDialog?.addEventListener('click', (event) => {
  if (event.target === editDialog) editDialog.close()
})
