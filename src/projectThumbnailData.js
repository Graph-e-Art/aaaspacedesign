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

export function projectPreviewSlides(project, maxSlides = 3) {
  const limit = Math.max(0, Math.floor(Number(maxSlides) || 0))
  if (!limit) return []

  const media = Array.isArray(project?.media) ? project.media : []
  const imageEntries = media.filter((item) => item?.type === 'image' && item.url)

  if (project?.image_url && !imageEntries.some((item) => item.url === project.image_url)) {
    imageEntries.push({ type: 'image', url: project.image_url, label: 'Project cover' })
  }

  const slides = []
  const seenImages = new Set()
  for (const item of imageEntries) {
    const src = normalizeProjectImageUrl(item.url, 'w640')
    if (!src || seenImages.has(src)) continue
    seenImages.add(src)
    slides.push({ type: 'image', key: `image:${src}`, src, label: item.label || '' })
    if (slides.length >= limit) return slides
  }

  const videoEntries = media.filter((item) => item?.type === 'drive' && driveFileId(item.url))
  if (!videoEntries.length && project?.embed_url && driveFileId(project.embed_url)) {
    videoEntries.push({ type: 'drive', url: project.embed_url, label: 'Project video' })
  }

  const seenVideos = new Set()
  for (const item of videoEntries) {
    const videoId = driveFileId(item.url)
    if (!videoId || seenVideos.has(videoId)) continue
    seenVideos.add(videoId)
    slides.push({
      type: 'video',
      key: `video:${videoId}`,
      src: driveVideoPreviewUrl(videoId),
      poster: driveVideoPosterUrl(videoId),
      label: item.label || 'Project video',
    })
    if (slides.length >= limit) break
  }

  return slides
}

export function projectImageSources(project, limit = 3) {
  return projectPreviewSlides(project, limit)
    .filter((slide) => slide.type === 'image')
    .map((slide) => normalizeProjectImageUrl(slide.src))
    .slice(0, limit)
}
