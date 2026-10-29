import React, { useEffect, useMemo, useRef, useState } from 'react'
import { normalizeProjectImageUrl, projectImageSources, projectPreviewSlides } from './projectThumbnailData.js'
export { normalizeProjectImageUrl, projectImageSources, projectPreviewSlides }

const SLIDE_INTERVAL_MS = 4000
const FADE_DURATION_MS = 1200
const ZOOM_DURATION_MS = 1400

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
  const [isHovering, setIsHovering] = useState(false)
  const [hasFocus, setHasFocus] = useState(false)
  const [failedSlides, setFailedSlides] = useState([])
  const slides = useMemo(() => projectPreviewSlides(project), [project])
  const signature = slides.map((slide) => slide.key).join('\u001f')
  const isPaused = isHovering || hasFocus

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
    const card = rootRef.current?.closest('.gallery-image')
    if (!card) return undefined

    const hoverPreference = window.matchMedia('(hover: hover) and (pointer: fine)')
    const onMouseEnter = () => {
      if (hoverPreference.matches) setIsHovering(true)
    }
    const onMouseLeave = () => setIsHovering(false)
    const onFocusIn = () => setHasFocus(true)
    const onFocusOut = (event) => {
      if (!card.contains(event.relatedTarget)) setHasFocus(false)
    }

    card.addEventListener('mouseenter', onMouseEnter)
    card.addEventListener('mouseleave', onMouseLeave)
    card.addEventListener('focusin', onFocusIn)
    card.addEventListener('focusout', onFocusOut)
    return () => {
      card.removeEventListener('mouseenter', onMouseEnter)
      card.removeEventListener('mouseleave', onMouseLeave)
      card.removeEventListener('focusin', onFocusIn)
      card.removeEventListener('focusout', onFocusOut)
    }
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
    if (outgoingIndex === null) return undefined
    const cleanupTimer = window.setTimeout(() => setOutgoingIndex(null), Math.max(FADE_DURATION_MS, ZOOM_DURATION_MS) + 40)
    return () => window.clearTimeout(cleanupTimer)
  }, [outgoingIndex])

  useEffect(() => {
    if (slides.length < 2 || !inView || !pageVisible || reducedMotion || isPaused) return undefined
    const timer = window.setTimeout(() => {
      setOutgoingIndex(activeIndex)
      setActiveIndex((activeIndex + 1) % slides.length)
    }, SLIDE_INTERVAL_MS)
    return () => window.clearTimeout(timer)
  }, [activeIndex, inView, pageVisible, reducedMotion, isPaused, signature, slides.length])

  if (!slides.length) {
    return <span className="project-thumbnail-empty" aria-hidden="true">Project preview coming soon</span>
  }

  const currentIndex = activeIndex % slides.length
  const nextIndex = slides.length > 1 ? (currentIndex + 1) % slides.length : null
  const shouldRunVideo = inView && pageVisible && !reducedMotion
  const shouldPreloadNext = inView && pageVisible && !reducedMotion
  const visibleIndices = new Set([currentIndex, outgoingIndex, shouldPreloadNext ? nextIndex : null].filter((index) => index !== null))

  return (
    <span ref={rootRef} className={`project-thumbnail-mosaic project-thumbnail-mosaic-${slides.length}`} aria-hidden="true">
      {slides.map((slide, index) => {
        if (!visibleIndices.has(index)) return null
        const isCurrent = index === currentIndex
        const isOutgoing = index === outgoingIndex
        const isFailed = failedSlides.includes(slide.key)
        const slideClass = [
          'project-thumbnail-slide',
          isCurrent ? 'is-current' : isOutgoing ? 'is-outgoing' : 'is-next',
          slide.type === 'video' ? 'is-video' : 'is-image',
        ].join(' ')
        return (
          <span className={slideClass} key={slide.key}>
            {isFailed ? (
              <span className="project-thumbnail-empty">Preview unavailable</span>
            ) : slide.type === 'video' ? (
              <>
                <img className="project-thumbnail-video-poster" src={slide.poster} alt="" loading="lazy" decoding="async" referrerPolicy="no-referrer" />
                {(isCurrent || isOutgoing) && shouldRunVideo && <iframe src={slide.src} title={`${slide.label} autoplay preview`} allow="autoplay; fullscreen" allowFullScreen loading="lazy" referrerPolicy="strict-origin-when-cross-origin" tabIndex={-1} />}
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
      <span className="project-thumbnail-indicator">
        <span className="project-thumbnail-count"><strong>{String(currentIndex + 1).padStart(2, '0')}</strong><span> / {String(slides.length).padStart(2, '0')}</span></span>
      </span>
    </span>
  )
}
