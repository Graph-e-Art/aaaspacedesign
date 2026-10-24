function driveFileId(url) {
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

export function normalizeProjectImageUrl(url, size = 'w1200') {
  const value = String(url || '').trim()
  if (!value) return ''
  const id = driveFileId(value)
  return id ? `https://drive.google.com/thumbnail?id=${encodeURIComponent(id)}&sz=${size}` : value
}

function driveVideoPreviewUrl(id) {
  const preview = new URL(`https://drive.google.com/file/d/${encodeURIComponent(id)}/preview`)
  preview.searchParams.set('autoplay', '1')
  preview.searchParams.set('mute', '1')
  return preview.toString()
}

function driveVideoPosterUrl(id) {
  return `https://drive.google.com/thumbnail?id=${encodeURIComponent(id)}&sz=w640`
}

export function projectPreviewSlides(project, maxImages = 3) {
  const media = Array.isArray(project?.media) ? project.media : []
  const imageEntries = media.filter((item) => item?.type === 'image' && item.url)

  if (project?.image_url && !imageEntries.some((item) => item.url === project.image_url)) {
    imageEntries.push({ type: 'image', url: project.image_url, label: 'Project cover' })
  }

  const seenImages = new Set()
  const images = []
  for (const item of imageEntries) {
    const src = normalizeProjectImageUrl(item.url, 'w640')
    if (!src || seenImages.has(src)) continue
    seenImages.add(src)
    images.push({ type: 'image', key: `image:${src}`, src, label: item.label || '' })
    if (images.length >= maxImages) break
  }

  const videoEntry = media.find((item) => item?.type === 'drive' && driveFileId(item.url))
    || (project?.embed_url ? { type: 'drive', url: project.embed_url, label: 'Project video' } : null)
  const videoId = videoEntry ? driveFileId(videoEntry.url) : ''
  const video = videoId ? {
    type: 'video',
    key: `video:${videoId}`,
    src: driveVideoPreviewUrl(videoId),
    poster: driveVideoPosterUrl(videoId),
    label: videoEntry.label || 'Project video',
  } : null

  return video ? [...images, video] : images
}

export function projectImageSources(project, limit = 3) {
  return projectPreviewSlides(project, limit)
    .filter((slide) => slide.type === 'image')
    .map((slide) => normalizeProjectImageUrl(slide.src))
    .slice(0, limit)
}
