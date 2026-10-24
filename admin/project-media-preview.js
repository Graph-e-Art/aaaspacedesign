export function getDriveFileId(url) {
  try {
    const parsed = new URL(String(url))
    const host = parsed.hostname.toLowerCase()
    const isDriveHost = host === 'drive.google.com' || host.endsWith('.drive.google.com')
    if (!isDriveHost) return ''
    return parsed.pathname.match(/\/file\/d\/([^/]+)/)?.[1] || parsed.searchParams.get('id') || ''
  } catch {
    return ''
  }
}

export function getDriveThumbnailUrl(url) {
  const id = getDriveFileId(url)
  return id ? `https://drive.google.com/thumbnail?id=${encodeURIComponent(id)}&sz=w640` : ''
}

export function getDriveVideoPreviewUrl(url) {
  const id = getDriveFileId(url)
  if (!id) return ''
  const preview = new URL(`https://drive.google.com/file/d/${encodeURIComponent(id)}/preview`)
  preview.searchParams.set('autoplay', '1')
  preview.searchParams.set('mute', '1')
  return preview.toString()
}

function createPlaceholder(message) {
  const placeholder = document.createElement('span')
  placeholder.className = 'media-preview-placeholder'
  placeholder.textContent = message
  return placeholder
}

function parseHttpUrl(value) {
  try {
    const parsed = new URL(value)
    return ['http:', 'https:'].includes(parsed.protocol) ? parsed : null
  } catch {
    return null
  }
}

function renderMediaPreview(row) {
  if (!row.isConnected) return

  const preview = row.querySelector('[data-media-preview]')
  const mediaInput = row.querySelector('[name="media_url[]"]')
  const typeInput = row.querySelector('[name="media_type[]"]')
  if (!preview || !mediaInput || !typeInput) return

  const value = mediaInput.value.trim()
  preview.replaceChildren()
  preview.dataset.kind = ''

  if (!value) {
    preview.append(createPlaceholder('Paste a link to preview'))
    return
  }

  const parsed = parseHttpUrl(value)
  if (!parsed) {
    preview.append(createPlaceholder('Enter a valid image or Drive link'))
    return
  }

  if (typeInput.value === 'drive') {
    const videoPreviewUrl = getDriveVideoPreviewUrl(parsed.toString())
    const posterUrl = getDriveThumbnailUrl(parsed.toString())
    if (!videoPreviewUrl) {
      preview.append(createPlaceholder('Use a Google Drive video link'))
      return
    }

    preview.dataset.kind = 'video'
    const poster = document.createElement('img')
    poster.className = 'media-preview-poster'
    poster.src = posterUrl
    poster.alt = 'Google Drive video poster'
    poster.loading = 'lazy'
    poster.referrerPolicy = 'no-referrer'
    poster.addEventListener('error', () => poster.remove(), { once: true })

    const player = document.createElement('iframe')
    player.className = 'media-preview-player'
    player.src = videoPreviewUrl
    player.title = 'Google Drive video preview'
    player.allow = 'autoplay; fullscreen'
    player.allowFullscreen = true
    player.loading = 'lazy'
    player.referrerPolicy = 'strict-origin-when-cross-origin'
    player.addEventListener('error', () => {
      player.remove()
      preview.classList.add('media-preview--poster')
      if (!poster.isConnected) preview.append(poster)
      if (!preview.querySelector('img')) preview.append(createPlaceholder('Video preview unavailable'))
    }, { once: true })

    preview.classList.remove('media-preview--poster')
    preview.append(poster, player)
    return
  }

  preview.dataset.kind = 'image'
  const image = document.createElement('img')
  image.alt = 'Project image preview'
  image.loading = 'lazy'
  image.referrerPolicy = 'no-referrer'
  image.src = getDriveThumbnailUrl(parsed.toString()) || parsed.toString()
  image.addEventListener('error', () => {
    preview.replaceChildren(createPlaceholder('Image preview unavailable'))
  }, { once: true })
  preview.append(image)
}

const previewTimers = new WeakMap()

function scheduleMediaPreview(row) {
  const previousTimer = previewTimers.get(row)
  if (previousTimer) window.clearTimeout(previousTimer)
  previewTimers.set(row, window.setTimeout(() => renderMediaPreview(row), 280))
}

function addMediaRow() {
  const list = document.getElementById('media-rows')
  const template = list?.querySelector('.media-row')
  if (!list || !template) return

  const row = template.cloneNode(true)
  const typeInput = row.querySelector('[name="media_type[]"]')
  const mediaInput = row.querySelector('[name="media_url[]"]')
  const labelInput = row.querySelector('[name="media_label[]"]')
  if (typeInput) typeInput.value = 'image'
  if (mediaInput) mediaInput.value = ''
  if (labelInput) labelInput.value = ''
  list.append(row)
  renderMediaPreview(row)
}

function removeMediaRow(button) {
  const rows = document.querySelectorAll('.media-row')
  const row = button.closest('.media-row')
  if (rows.length > 1 && row) row.remove()
}

function initializeProjectMediaPreviews() {
  const list = document.getElementById('media-rows')
  if (!list) return

  list.querySelectorAll('.media-row').forEach(renderMediaPreview)
  list.addEventListener('input', (event) => {
    if (event.target.matches('[name="media_url[]"]')) {
      scheduleMediaPreview(event.target.closest('.media-row'))
    }
  })
  list.addEventListener('change', (event) => {
    if (event.target.matches('[name="media_type[]"], [name="media_url[]"]')) {
      renderMediaPreview(event.target.closest('.media-row'))
    }
  })
  document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return
    if (event.target.closest('[data-action="add-media"]')) addMediaRow()
    const removeButton = event.target.closest('[data-action="remove-media"]')
    if (removeButton) removeMediaRow(removeButton)
  })
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeProjectMediaPreviews, { once: true })
  } else {
    initializeProjectMediaPreviews()
  }
}
