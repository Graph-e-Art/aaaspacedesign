import React, { useEffect, useMemo, useRef, useState } from 'react'
import { normalizeProjectImageUrl, projectImageSources, projectPreviewSlides } from './projectThumbnailData.js'
export { normalizeProjectImageUrl, projectImageSources, projectPreviewSlides }
const SLIDE_INTERVAL_MS = 4000
const FADE_DURATION_MS = 620
function prefersReducedMotion() {
  return typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches
}
export default function ProjectThumbnail({ project }) {
  const rootRef = useRef(null)
  const [activeIndex, setActiveIndex] = useState(0)
  const [outgoingIndex, setOutgoingIndex] = useState(null)
  const [inView, setInView] = useState(false)
  const [pageVisible, setPageVisible] = useState(() => typeof document === 'undefined' || !document.hidden)
  const [reducedMotion, setReducedMotion] = useState(prefersReducedMotion)
  const [failedSlides, setFailedSlides] = useState([])
  const slides = useMemo(() => projectPreviewSlides(project), [project])
  const signature = slides.map((slide) => slide.key).join('\u001f')
  useEffect(() => {
    setActiveIndex(0)
    setOutgoingIndex(null)
    setFailedSlides([])
  }, [signature])
  useEffect(() => {
    const node = rootRef.current
    if (!node || !('IntersectionObserver' in window)) return undefined
    const observer = new IntersectionObserver(([entry]) => {
      setInView(entry.isIntersecting)
    }, { threshold: 0.15 })
    observer.observe(node)
    return () => observer.disconnect()
  }, [])
  useEffect(() => {
    const updateVisibility = () => setPageVisible(!document.hidden)
    document.addEventListener('visibilitychange', updateVisibility)
    return () => document.removeEventListener('visibilitychange', updateVisibility)
  }, [])
  useEffect(() => {
    const query = window.matchMedia('(prefers-reduced-motion: reduce)')
    const updateMotionPreference = () => setReducedMotion(query.matches)
    if (query.addEventListener) query.addEventListener('change', updateMotionPreference)
    else query.addListener?.(updateMotionPreference)
    return () => {
      if (query.removeEventListener) query.removeEventListener('change', updateMotionPreference)
      else query.removeListener?.(updateMotionPreference)
    }
  }, [])
  useEffect(() => {
    if (!inView || !pageVisible || reducedMotion) setOutgoingIndex(null)
  }, [inView, pageVisible, reducedMotion])
  useEffect(() => {
    if (slides.length < 2 || !inView || !pageVisible || reducedMotion) return undefined
    let fadeTimer
    const interval = window.setInterval(() => {
      setActiveIndex((currentIndex) => {
        const nextIndex = (currentIndex + 1) % slides.length
        setOutgoingIndex(currentIndex)
        window.clearTimeout(fadeTimer)
        fadeTimer = window.setTimeout(() => setOutgoingIndex(null), FADE_DURATION_MS + 40)
        return nextIndex
      })
    }, SLIDE_INTERVAL_MS)
    return () => {
      window.clearInterval(interval)
      window.clearTimeout(fadeTimer)
    }
  }, [inView, pageVisible, reducedMotion, signature, slides.length])
  if (!slides.length) {
    return <span className="project-thumbnail-empty" aria-hidden="true">Project preview coming soon</span>
  }
  const currentIndex = activeIndex % slides.length
  const shouldRunVideo = inView && pageVisible && !reducedMotion
  const visibleIndices = new Set([currentIndex, outgoingIndex].filter((index) => index !== null))
  return (
    <span ref={rootRef} className={`project-thumbnail-mosaic project-thumbnail-mosaic-${slides.length}`} aria-hidden="true">
      {slides.map((slide, index) => {
        if (!visibleIndices.has(index)) return null
        const isCurrent = index === currentIndex
        const isFailed = failedSlides.includes(slide.key)
        const slideClass = [
          'project-thumbnail-slide',
          isCurrent ? 'is-current' : 'is-outgoing',
          slide.type === 'video' ? 'is-video' : 'is-image',
        ].join(' ')
        return (
          <span className={slideClass} key={slide.key}>
            {isFailed ? (
              <span className="project-thumbnail-empty">Preview unavailable</span>
            ) : slide.type === 'video' ? (
              <>
                <img className="project-thumbnail-video-poster" src={slide.poster} alt="" loading="lazy" decoding="async" referrerPolicy="no-referrer" />
                {shouldRunVideo && <iframe src={slide.src} title={`${slide.label} autoplay preview`} allow="autoplay; fullscreen" allowFullScreen loading="lazy" referrerPolicy="strict-origin-when-cross-origin" tabIndex={-1} />}
              </>
            ) : (
              <img
                src={slide.src}
                alt=""
                loading="lazy"
                decoding="async"
                fetchPriority="low"
                onError={() => setFailedSlides((current) => current.includes(slide.key) ? current : [...current, slide.key])}
              />
            )}
          </span>
        )
      })}
    </span>
  )
}
