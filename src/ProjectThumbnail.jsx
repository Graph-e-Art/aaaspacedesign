import React, { useEffect, useState } from 'react'

function driveFileId(url) {
  try {
    const parsed = new URL(String(url))
    const isDriveHost = parsed.hostname === 'drive.google.com' || parsed.hostname.endsWith('.drive.google.com')
    if (!isDriveHost) return ''
    return parsed.pathname.match(/\/file\/d\/([^/]+)/)?.[1] || parsed.searchParams.get('id') || ''
  } catch {
    return ''
  }
}

export function normalizeProjectImageUrl(url) {
  const value = String(url || '').trim()
  if (!value) return ''
  const id = driveFileId(value)
  return id ? `https://drive.google.com/thumbnail?id=${encodeURIComponent(id)}&sz=w1200` : value
}

export function projectImageSources(project, limit = 3) {
  const mediaImages = Array.isArray(project?.media)
    ? project.media.filter((item) => item?.type === 'image' && item.url).map((item) => item.url)
    : []
  const candidates = [...mediaImages, project?.image_url].filter(Boolean)
  const seen = new Set()
  const images = []

  for (const candidate of candidates) {
    const url = normalizeProjectImageUrl(candidate)
    if (url && !seen.has(url)) {
      seen.add(url)
      images.push(url)
    }
    if (images.length >= limit) break
  }

  return images
}

export default function ProjectThumbnail({ project }) {
  const sources = projectImageSources(project, 3)
  const signature = sources.join('\u001f')
  const [failed, setFailed] = useState([])
  useEffect(() => setFailed([]), [signature])

  const visibleSources = sources
    .map((url, index) => ({ url, index }))
    .filter(({ index }) => !failed.includes(index))

  if (!visibleSources.length) {
    return <span className="project-thumbnail-empty" aria-hidden="true">Project preview coming soon</span>
  }

  return (
    <span className={`project-thumbnail-mosaic project-thumbnail-mosaic-${visibleSources.length}`} aria-hidden="true">
      {visibleSources.map(({ url, index }) => (
        <span className="project-thumbnail-tile" key={url}>
          <img
            src={url}
            alt=""
            loading="lazy"
            onError={() => setFailed((current) => current.includes(index) ? current : [...current, index])}
          />
        </span>
      ))}
    </span>
  )
}
