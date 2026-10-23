import React, { useEffect, useRef, useState } from 'react'
import { ArrowDown, ArrowUpRight, X } from 'lucide-react'
import './service-section.css'

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
  const dialogRef = useRef(null)

  useEffect(() => {
    const dialog = dialogRef.current
    if (!dialog) return
    if (isOpen && !dialog.open) dialog.showModal()
    if (!isOpen && dialog.open) dialog.close()
  }, [isOpen])

  const closeServices = () => {
    if (dialogRef.current?.open) dialogRef.current.close()
    setIsOpen(false)
  }

  const startEnquiry = () => {
    closeServices()
    onContact()
  }

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
        <figure className="service-feature-image">
          <img src="/assets/drive-gallery/selected/custom-sofa-feature.jpeg" alt="Dark brown tufted sofa with a black woven frame, made by AA Associate." width="2048" height="1536" loading="lazy" decoding="async" />
          <figcaption>Custom sofa · Furniture works</figcaption>
        </figure>
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
