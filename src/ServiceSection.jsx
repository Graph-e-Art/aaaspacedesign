import React, { useEffect, useRef, useState } from 'react'
import { ArrowDown, ArrowUpRight, ChevronLeft, ChevronRight, Pause, Play, X } from 'lucide-react'
import './service-section.css'

const furnitureSlides = [
  {
    src: '/assets/drive-gallery/selected/custom-sofa-feature.jpeg',
    alt: 'Dark brown tufted custom sofa with a woven outer frame.',
    label: 'Custom sofa',
    width: 2048,
    height: 1536,
  },
  {
    src: '/assets/drive-gallery/selected/custom-chair-feature.jpeg',
    alt: 'Upholstered lounge chair with a dark frame against a teal studio wall.',
    label: 'Custom lounge chair',
    width: 828,
    height: 598,
  },
  {
    src: '/assets/drive-gallery/selected/carpentry-detail.jpg',
    alt: 'Upholstered seating and furniture workmanship in the workshop.',
    label: 'Upholstery & seating',
    width: 1600,
    height: 1200,
  },
]

const additionalServices = [
  { title: 'Home Interior', description: 'Interiors shaped around your home, rooms and everyday routines.' },
  { title: 'Office Interior', description: 'Workspaces planned around people, purpose and daily operations.' },
  { title: 'Kitchen', description: 'Kitchen interiors planned around your space and daily use.' },
  { title: 'Bedroom', description: 'Considered layouts, furniture and finishes for restful bedrooms.' },
  { title: 'Living Room', description: 'Seating, layout and finishes composed as one welcoming space.' },
  { title: 'Renovation', description: 'Refresh an existing space through planned, coordinated updates.' },
  { title: 'IT & Mechanical Infrastructure', description: 'Structured cabling and building services coordinated with interiors.' },
  { title: 'False Ceiling & Lighting', description: 'Ceiling details and lighting integrated into the interior plan.' },
]

export default function ServiceSection({ onContact }) {
  const [isOpen, setIsOpen] = useState(false)
  const [activeSlide, setActiveSlide] = useState(0)
  const [isPlaying, setIsPlaying] = useState(() => !window.matchMedia('(prefers-reduced-motion: reduce)').matches)
  const [isHovered, setIsHovered] = useState(false)
  const [hasFocus, setHasFocus] = useState(false)
  const dialogRef = useRef(null)

  useEffect(() => {
    const dialog = dialogRef.current
    if (!dialog) return
    if (isOpen && !dialog.open) dialog.showModal()
    if (!isOpen && dialog.open) dialog.close()
  }, [isOpen])

  useEffect(() => {
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
    const stopOnReducedMotion = (event) => {
      if (event.matches) setIsPlaying(false)
    }
    const supportsEventListener = typeof mediaQuery.addEventListener === 'function'
    if (supportsEventListener) mediaQuery.addEventListener('change', stopOnReducedMotion)
    else mediaQuery.addListener(stopOnReducedMotion)
    return () => {
      if (supportsEventListener) mediaQuery.removeEventListener('change', stopOnReducedMotion)
      else mediaQuery.removeListener(stopOnReducedMotion)
    }
  }, [])

  useEffect(() => {
    if (!isPlaying || isHovered || hasFocus) return undefined
    const timer = window.setInterval(() => {
      setActiveSlide((current) => (current + 1) % furnitureSlides.length)
    }, 5200)
    return () => window.clearInterval(timer)
  }, [isPlaying, isHovered, hasFocus])

  const showPreviousSlide = () => {
    setActiveSlide((current) => (current - 1 + furnitureSlides.length) % furnitureSlides.length)
  }
  const showNextSlide = () => {
    setActiveSlide((current) => (current + 1) % furnitureSlides.length)
  }
  const closeServices = () => {
    if (dialogRef.current?.open) dialogRef.current.close()
    setIsOpen(false)
  }
  const startEnquiry = () => {
    closeServices()
    onContact()
  }
  const handleCarouselBlur = (event) => {
    if (!event.currentTarget.contains(event.relatedTarget)) setHasFocus(false)
  }

  const currentSlide = furnitureSlides[activeSlide]

  return <>
    <section id="services" className="service-feature-section section-pad" aria-labelledby="service-feature-title">
      <div className="service-feature-card">
        <div className="service-feature-copy">
          <p className="eyebrow">Our core craft · 01</p>
          <h2 id="service-feature-title">Custom furniture <span>&amp; sofa making</span></h2>
          <p className="service-feature-description">Furniture making is where our work begins. We design and make custom sofas and furniture, then bring that same care into the wider interior.</p>
          <div className="service-feature-actions">
            <button type="button" className="button dark" onClick={startEnquiry}>Discuss custom furniture <ArrowUpRight size={16} aria-hidden="true" /></button>
            <button type="button" className="service-more-button" aria-haspopup="dialog" aria-controls="more-services-dialog" onClick={() => setIsOpen(true)}>More services <ArrowDown size={15} aria-hidden="true" /></button>
          </div>
        </div>
        <div
          className="service-feature-gallery"
          role="region"
          aria-roledescription="carousel"
          aria-label="Custom sofa and furniture photos"
          onMouseEnter={() => setIsHovered(true)}
          onMouseLeave={() => setIsHovered(false)}
          onFocusCapture={() => setHasFocus(true)}
          onBlurCapture={handleCarouselBlur}
        >
          <figure className="service-slide" key={currentSlide.src}>
            <img src={currentSlide.src} alt={currentSlide.alt} width={currentSlide.width} height={currentSlide.height} loading="eager" decoding="async" />
            <figcaption>{currentSlide.label} <span>{String(activeSlide + 1).padStart(2, '0')} / {String(furnitureSlides.length).padStart(2, '0')}</span></figcaption>
          </figure>
          <button type="button" className="service-carousel-arrow service-carousel-previous" aria-label="Previous furniture photo" onClick={showPreviousSlide}><ChevronLeft size={20} aria-hidden="true" /></button>
          <button type="button" className="service-carousel-arrow service-carousel-next" aria-label="Next furniture photo" onClick={showNextSlide}><ChevronRight size={20} aria-hidden="true" /></button>
          <div className="service-carousel-controls" role="group" aria-label="Slideshow controls">
            <div className="service-carousel-dots" role="group" aria-label="Choose a furniture photo">
              {furnitureSlides.map((slide, index) => (
                <button type="button" className={`service-carousel-dot${index === activeSlide ? ' is-active' : ''}`} key={slide.src} aria-label={`Show ${slide.label} photo`} aria-current={index === activeSlide ? 'true' : undefined} onClick={() => setActiveSlide(index)}><span aria-hidden="true" /></button>
              ))}
            </div>
            <button type="button" className="service-carousel-toggle" aria-label={isPlaying ? 'Pause slideshow' : 'Play slideshow'} aria-pressed={!isPlaying} onClick={() => setIsPlaying((playing) => !playing)}>{isPlaying ? <Pause size={15} aria-hidden="true" /> : <Play size={15} aria-hidden="true" />}</button>
          </div>
          <span className="service-carousel-status" aria-live={isPlaying ? 'off' : 'polite'} aria-atomic="true">{currentSlide.label}, photo {activeSlide + 1} of {furnitureSlides.length}</span>
        </div>
      </div>
    </section>

    <dialog id="more-services-dialog" ref={dialogRef} className="service-more-dialog" aria-labelledby="more-services-title" onCancel={(event) => { event.preventDefault(); closeServices() }} onClose={() => setIsOpen(false)} onClick={(event) => { if (event.target === event.currentTarget) closeServices() }}>
      <header className="service-dialog-header">
        <div>
          <p className="eyebrow">Beyond furniture</p>
          <h2 id="more-services-title">More services</h2>
          <p>Choose a service to start a conversation with our team.</p>
        </div>
        <button type="button" className="service-dialog-close" aria-label="Close services" onClick={closeServices}><X size={20} aria-hidden="true" /></button>
      </header>
      <div className="service-dialog-body">
        <div className="service-dialog-grid">
          {additionalServices.map((service, index) => (
            <button type="button" className="service-option" key={service.title} onClick={startEnquiry}>
              <span className="service-option-number">{String(index + 1).padStart(2, '0')}</span>
              <span className="service-option-copy"><strong>{service.title}</strong><span>{service.description}</span></span>
              <ArrowUpRight className="service-option-arrow" size={17} aria-hidden="true" />
            </button>
          ))}
        </div>
      </div>
    </dialog>
  </>
}
