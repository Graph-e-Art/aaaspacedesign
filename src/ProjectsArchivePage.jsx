import React, { useEffect, useState } from 'react'
import { ArrowDown, ArrowUpRight, ChevronLeft, ChevronRight, X } from 'lucide-react'
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

function projectDate(project) {
  return `${new Date(2000, project.month - 1, 1).toLocaleString('en', { month: 'short' })} ${project.year}`
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
  const [selectedProject, setSelectedProject] = useState(null)
  const [selectedMedia, setSelectedMedia] = useState(0)
  const [selectedWorkTag, setSelectedWorkTag] = useState(null)
  const [tagProjects, setTagProjects] = useState([])
  const [tagTotal, setTagTotal] = useState(0)
  const [tagLoading, setTagLoading] = useState(false)
  const [tagLoadingMore, setTagLoadingMore] = useState(false)
  const [tagError, setTagError] = useState('')

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

  useEffect(() => {
    if (!selectedProject && !selectedWorkTag) return undefined
    const onKeyDown = (event) => {
      if (event.key === 'Escape') {
        setSelectedProject(null)
        setSelectedWorkTag(null)
      }
      if (selectedProject && event.key === 'ArrowLeft') {
        const count = projectMedia(selectedProject).length
        if (count > 1) setSelectedMedia((index) => (index - 1 + count) % count)
      }
      if (selectedProject && event.key === 'ArrowRight') {
        const count = projectMedia(selectedProject).length
        if (count > 1) setSelectedMedia((index) => (index + 1) % count)
      }
    }
    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [selectedProject, selectedWorkTag])

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

  const openProject = (project) => {
    setSelectedWorkTag(null)
    setSelectedProject(project)
    setSelectedMedia(0)
  }

  const openWorkTag = async (tag) => {
    if (!tag?.id) return
    setSelectedProject(null)
    setSelectedWorkTag(tag)
    setTagProjects([])
    setTagTotal(0)
    setTagLoading(true)
    setTagError('')
    try {
      const query = new URLSearchParams({ limit: String(PAGE_SIZE), offset: '0', work_tag_id: String(tag.id) })
      const response = await fetch(`/api/projects.php?${query.toString()}`)
      const result = await response.json().catch(() => null)
      if (!response.ok || !result?.ok) throw new Error(result?.message || 'Related projects could not be loaded.')
      setTagProjects(result.projects || [])
      setTagTotal(result.total || 0)
    } catch (loadError) {
      setTagError(loadError.message || 'Related projects could not be loaded.')
    } finally {
      setTagLoading(false)
    }
  }

  const loadMoreTagProjects = async () => {
    if (!selectedWorkTag?.id) return
    setTagLoadingMore(true)
    setTagError('')
    try {
      const query = new URLSearchParams({ limit: String(PAGE_SIZE), offset: String(tagProjects.length), work_tag_id: String(selectedWorkTag.id) })
      const response = await fetch(`/api/projects.php?${query.toString()}`)
      const result = await response.json().catch(() => null)
      if (!response.ok || !result?.ok) throw new Error(result?.message || 'More related projects could not be loaded.')
      setTagProjects((current) => [...current, ...(result.projects || [])])
      setTagTotal(result.total || 0)
    } catch (loadError) {
      setTagError(loadError.message || 'More related projects could not be loaded.')
    } finally {
      setTagLoadingMore(false)
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
            <p>Browse project galleries, then select a work tag to find related projects.</p>
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
              const hasThumbnail = projectPreviewSlides(project).length > 0
              return <article className="archive-card all-projects-project" key={project.id}>
                <button type="button" className="all-projects-project-trigger" onClick={() => openProject(project)} aria-label={`Open ${project.title} project gallery`}>
                  {hasThumbnail ? <span className="all-projects-card-thumbnail"><ProjectThumbnail project={project} /></span> : <span className="archive-placeholder all-projects-card-placeholder"><strong>{String(project.year)}</strong><span>Project file</span></span>}
                  <span className="all-projects-project-copy">
                    <span className="all-projects-project-meta">{projectDate(project)}{project.client_name ? ` · ${project.client_name}` : ''}</span>
                    <strong>{project.title}</strong>
                    <span className="all-projects-project-description">{project.description}</span>
                    <span className="all-projects-open-label">Open project gallery <ArrowUpRight size={14} /></span>
                  </span>
                </button>
              </article>
            })}
          </div>
          {error && <p className="archive-error" role="alert">{error}</p>}
          {projects.length < total && <div className="all-projects-load-more"><span>Showing {projects.length} of {total}</span><button className="button dark" type="button" onClick={loadMore} disabled={loadingMore}>{loadingMore ? 'Loading…' : 'Load more projects'} <ArrowDown size={16} /></button></div>}
        </> : <p className="archive-status">No published projects match these filters yet.</p>}
      </main>
      <footer className="all-projects-footer"><span>AA ASSOCIATE</span><a href="/">Return to homepage</a></footer>

      {selectedProject && (() => {
        const media = projectMedia(selectedProject)
        const currentMedia = media[selectedMedia]
        const moveMedia = (direction) => setSelectedMedia((index) => (index + direction + media.length) % media.length)
        return <div className="dynamic-project-lightbox" role="dialog" aria-modal="true" aria-labelledby="archive-project-title" onClick={() => setSelectedProject(null)}>
          <div className="dynamic-project-panel" onClick={(event) => event.stopPropagation()}>
            <div className="dynamic-project-header"><button type="button" className="lightbox-close-button" onClick={() => setSelectedProject(null)} aria-label="Close project gallery"><X size={16} /></button></div>
            <div className="dynamic-project-body">
              <div className="dynamic-project-gallery">
                <div className="dynamic-project-viewer">
                  {currentMedia?.type === 'drive' ? <iframe src={mediaHref(currentMedia)} title={currentMedia.label || 'Project video'} allow="autoplay; fullscreen" allowFullScreen referrerPolicy="strict-origin-when-cross-origin" /> : currentMedia?.url ? <img src={currentMedia.url} alt={currentMedia.label || selectedProject.title} /> : <span className="dynamic-project-empty">Project media will be added soon.</span>}
                  {media.length > 1 && <>
                    <button type="button" className="dynamic-project-nav dynamic-project-nav-prev" onClick={() => moveMedia(-1)} aria-label="Previous project media"><ChevronLeft size={24} /></button>
                    <button type="button" className="dynamic-project-nav dynamic-project-nav-next" onClick={() => moveMedia(1)} aria-label="Next project media"><ChevronRight size={24} /></button>
                  </>}
                </div>
                {currentMedia && <div className="dynamic-project-caption"><span>{currentMedia.label || 'Project media'}</span><span>{selectedMedia + 1} / {media.length}</span></div>}
              </div>
              <aside className="dynamic-project-details">
                <h2 id="archive-project-title">{selectedProject.title}</h2>
                <p>{selectedProject.description}</p>
                {selectedProject.work_tags?.length > 0 && <div className="dynamic-project-work-tags">
                  <h3>Work tags</h3>
                  <ul>{selectedProject.work_tags.map((tag) => <li key={tag.id ?? tag.slug ?? tag.name}><button type="button" onClick={() => openWorkTag(tag)}>{tag.name}<ArrowUpRight size={12} /></button></li>)}</ul>
                </div>}
              </aside>
            </div>
            <div className="dynamic-project-footer"><p className="eyebrow">PROJECT MEDIA / ARCHIVE</p><span>{String(selectedProject.month).padStart(2, '0')} / {selectedProject.year}{selectedProject.client_name ? ` · ${selectedProject.client_name}` : ''}</span></div>
          </div>
        </div>
      })()}

      {selectedWorkTag && <div className="work-tag-projects-lightbox" role="dialog" aria-modal="true" aria-labelledby="work-tag-projects-title" onClick={() => setSelectedWorkTag(null)}>
        <div className="work-tag-projects-panel" onClick={(event) => event.stopPropagation()}>
          <div className="client-projects-header">
            <div><p className="eyebrow">RELATED PROJECTS / WORK TAG</p><h2 id="work-tag-projects-title">{selectedWorkTag.name}</h2><p>{tagTotal} project{tagTotal === 1 ? '' : 's'} with this work tag</p></div>
            <button type="button" className="lightbox-close-button" onClick={() => setSelectedWorkTag(null)} aria-label="Close related projects"><X size={16} /></button>
          </div>
          {tagLoading ? <p className="client-projects-status" role="status">Loading related projects…</p> : tagError && !tagProjects.length ? <p className="client-projects-status" role="alert">{tagError}</p> : tagProjects.length ? <>
            <div className="client-projects-grid">
              {tagProjects.map((project) => <button type="button" className="client-project-card work-tag-project-card" key={project.id} onClick={() => openProject(project)}>
                <span className="work-tag-project-thumb"><ProjectThumbnail project={project} /></span>
                <span className="client-project-card-copy"><small>{projectDate(project)}</small><strong>{project.title}</strong><span>{project.description}</span><em>Open project gallery <ArrowUpRight size={15} /></em></span>
              </button>)}
            </div>
            {tagError && <p className="archive-error" role="alert">{tagError}</p>}
            {tagProjects.length < tagTotal && <div className="all-projects-load-more"><span>Showing {tagProjects.length} of {tagTotal}</span><button className="button dark" type="button" onClick={loadMoreTagProjects} disabled={tagLoadingMore}>{tagLoadingMore ? 'Loading…' : 'Load more projects'} <ArrowDown size={16} /></button></div>}
          </> : <p className="client-projects-status">No published projects use this work tag yet.</p>}
        </div>
      </div>}
    </div>
  )
}
