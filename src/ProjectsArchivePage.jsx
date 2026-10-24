import React, { useEffect, useState } from 'react'
import { ArrowDown, ArrowUpRight } from 'lucide-react'
import ProjectThumbnail, { normalizeProjectImageUrl, projectPreviewSlides } from './ProjectThumbnail.jsx'

const PAGE_SIZE = 24

function projectMedia(project) {
  const media = Array.isArray(project.media) ? project.media : []
  const cover = project.image_url ? [{ type: 'image', url: project.image_url, label: 'Project cover' }] : []
  const withoutDuplicateCover = cover.filter((item) => !media.some((entry) => entry.type === 'image' && entry.url === item.url))
  return [...withoutDuplicateCover, ...media].map((item) => item.type === 'image' ? { ...item, url: normalizeProjectImageUrl(item.url) } : item)
}

function mediaHref(item) {
  if (item.type === 'image') return normalizeProjectImageUrl(item.url)
  if (item.type !== 'drive') return item.url
  return item.url.includes('/file/d/') ? item.url.replace(/\/view(?:\?.*)?$/, '/preview') : item.url
}

export default function ProjectsArchivePage() {
  const [projects, setProjects] = useState([])
  const [years, setYears] = useState([])
  const [months, setMonths] = useState([])
  const [year, setYear] = useState('')
  const [month, setMonth] = useState('')
  const [total, setTotal] = useState(0)
  const [loading, setLoading] = useState(true)
  const [loadingMore, setLoadingMore] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    const controller = new AbortController()
    const query = new URLSearchParams({ limit: String(PAGE_SIZE), offset: '0' })
    if (year) query.set('year', year)
    if (month) query.set('month', month)

    setLoading(true)
    setError('')
    setProjects([])
    setTotal(0)
    fetch(`/api/projects.php?${query.toString()}`, { signal: controller.signal })
      .then(async (response) => {
        const result = await response.json().catch(() => null)
        if (!response.ok || !result?.ok) throw new Error(result?.message || 'Project archive unavailable.')
        return result
      })
      .then((result) => {
        setProjects(result.projects || [])
        setTotal(result.total || 0)
        setYears(result.years || [])
        setMonths(result.months || [])
      })
      .catch((loadError) => {
        if (loadError.name !== 'AbortError') setError(loadError.message || 'Project archive unavailable.')
      })
      .finally(() => {
        if (!controller.signal.aborted) setLoading(false)
      })

    return () => controller.abort()
  }, [year, month])

  const loadMore = async () => {
    setLoadingMore(true)
    setError('')
    const query = new URLSearchParams({ limit: String(PAGE_SIZE), offset: String(projects.length) })
    if (year) query.set('year', year)
    if (month) query.set('month', month)
    try {
      const response = await fetch(`/api/projects.php?${query.toString()}`)
      const result = await response.json().catch(() => null)
      if (!response.ok || !result?.ok) throw new Error(result?.message || 'More projects could not be loaded.')
      setProjects((current) => [...current, ...(result.projects || [])])
      setTotal(result.total || 0)
    } catch (loadError) {
      setError(loadError.message || 'More projects could not be loaded.')
    } finally {
      setLoadingMore(false)
    }
  }

  return (
    <div className="all-projects-page">
      <header className="all-projects-header">
        <a href="/" aria-label="AA Associate home"><img src="/assets/aa-associate-logo-new-j.png" alt="AA Associate" /></a>
        <a className="all-projects-home-link" href="/">Back to home <ArrowUpRight size={15} /></a>
      </header>
      <main className="all-projects-main">
        <p className="eyebrow">AA ASSOCIATE / PORTFOLIO</p>
        <div className="all-projects-title-row">
          <div>
            <h1>Projects, in full.</h1>
            <p>Filter published project work by date. Each card previews up to three gallery images and links to its project media.</p>
          </div>
          <span className="all-projects-count">{total} published project{total === 1 ? '' : 's'}</span>
        </div>
        <div className="archive-filters all-projects-filters">
          <label>Year<select value={year} onChange={(event) => setYear(event.target.value)}><option value="">All years</option>{years.map((item) => <option key={item} value={item}>{item}</option>)}</select></label>
          <label>Month<select value={month} onChange={(event) => setMonth(event.target.value)}><option value="">All months</option>{months.map((item) => <option key={item} value={item}>{new Date(2000, item - 1, 1).toLocaleString('en', { month: 'long' })}</option>)}</select></label>
        </div>
        {loading ? <p className="archive-status" role="status">Loading projects…</p> : error && !projects.length ? <p className="archive-status archive-error" role="alert">{error}</p> : projects.length ? <>
          <div className="archive-grid all-projects-grid">
            {projects.map((project) => {
              const media = projectMedia(project)
              const hasThumbnail = projectPreviewSlides(project).length > 0
              return <article className="archive-card" key={project.id}>
                {hasThumbnail ? <div className="archive-card-thumbnail"><ProjectThumbnail project={project} /></div> : <div className="archive-placeholder"><strong>{String(project.year)}</strong><span>Project file</span></div>}
                <div>
                  <span>{new Date(2000, project.month - 1, 1).toLocaleString('en', { month: 'short' })} {project.year}{project.client_name ? ` · ${project.client_name}` : ''}</span>
                  <h2>{project.title}</h2>
                  <p>{project.description}</p>
                  <div className="project-media-links">{media.map((item, index) => <a key={`${item.url}-${index}`} href={mediaHref(item)} target="_blank" rel="noopener noreferrer">{item.type === 'drive' ? 'View video' : 'View image'} <ArrowUpRight size={14} /></a>)}</div>
                </div>
              </article>
            })}
          </div>
          {error && <p className="archive-error" role="alert">{error}</p>}
          {projects.length < total && <div className="all-projects-load-more"><span>Showing {projects.length} of {total}</span><button className="button dark" type="button" onClick={loadMore} disabled={loadingMore}>{loadingMore ? 'Loading…' : 'Load more projects'} <ArrowDown size={16} /></button></div>}
        </> : <p className="archive-status">No published projects match these filters yet.</p>}
      </main>
      <footer className="all-projects-footer"><span>AA ASSOCIATE</span><a href="/">Return to homepage</a></footer>
    </div>
  )
}
