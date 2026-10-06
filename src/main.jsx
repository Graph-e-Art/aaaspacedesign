import React, { useState } from 'react'
import { createRoot } from 'react-dom/client'
import { ArrowDown, ArrowUpRight, Check, ChevronDown, Expand, Menu, X } from 'lucide-react'
import './styles.css'

const A='/assets/'

const services=[
  ['Space Planning','Functional layouts that make every square metre work harder for your people and your business.'],
  ['Office Partitioning','Glass, wood, aluminium and fabric partition systems with clean, practical detailing.'],
  ['Commercial Interiors','Workplaces designed around your brand, workflow, comfort and long-term use.'],
  ['Civil & Structural Work','Coordinated site preparation, renovation, flooring, ceilings and structural work.'],
  ['Electrical, Plumbing & HVAC','Essential building systems planned with the interior from day one.'],
  ['IT Infrastructure','Voice, data, power cabling and technology-ready workspaces with tidy management.'],
  ['Carpentry, Glass & Turnkey','Custom joinery, glass installation and end-to-end project execution.'],
]

const process=[
  ['01','Discover','Share your brief, site information and priorities. We define the scope before design begins.'],
  ['02','Design','We translate requirements into a clear plan, coordinated drawings and material direction.'],
  ['03','Deliver','Our team coordinates installation, quality checks and handover with practical communication.'],
]

const projects=[
  {images:['9-16739.webp','9-16748.webp','9-16753.webp'],category:'Office Interiors',label:'Project name to be confirmed',alt:'Conceptual office interior with meeting table and glazed partitions'},
  {images:['9-16748.webp','9-16739.webp','9-16758.webp'],category:'Office Partitioning',label:'Project name to be confirmed',alt:'Conceptual office corridor with glass partitioning'},
  {images:['9-16758.webp','9-16753.webp','9-16739.webp'],category:'Commercial Interiors',label:'Project name to be confirmed',alt:'Conceptual commercial interior with custom joinery'},
  {images:['1-74.webp','1-107.webp','1-111.webp'],category:'Turnkey Projects',label:'Project details to be confirmed',alt:'Interior space with warm architectural materials'},
  {images:['1-27.webp','1-31.webp','1-127.webp'],category:'Space Planning',label:'Project details to be confirmed',alt:'Bright contemporary workspace interior'},
  {images:['1-107.webp','1-111.webp','1-74.webp'],category:'Commercial Interiors',label:'Project details to be confirmed',alt:'Commercial interior detail with considered finishes'},
]

function App(){
  const [menuOpen,setMenuOpen]=useState(false)
  const [openService,setOpenService]=useState(0)
  const [projectFilter,setProjectFilter]=useState('All')
  const [selectedProject,setSelectedProject]=useState(null)
  const [selectedImage,setSelectedImage]=useState(0)
  const [submitted,setSubmitted]=useState(false)
  const scrollTo=(id)=>{ document.getElementById(id)?.scrollIntoView({behavior:'smooth'}); setMenuOpen(false) }
  const openProject=(project)=>{ setSelectedProject(project); setSelectedImage(0) }
  const closeProject=()=>{ setSelectedProject(null); setSelectedImage(0) }
  const filters=['All',...new Set(projects.map(project=>project.category))]
  const visibleProjects=projects.filter(project=>projectFilter==='All'||project.category===projectFilter)
  return <div className="site">
    <header className="nav-wrap">
      <a className="brand" href="#top" aria-label="AA Space Design home"><img src={A+'aa-space-design-logo.png'} alt="AA Space Design" /></a>
      <button className="menu-toggle" onClick={()=>setMenuOpen(!menuOpen)} aria-label={menuOpen?'Close menu':'Open menu'}>{menuOpen?<X/>:<Menu/>}</button>
      <nav className={menuOpen?'nav open':'nav'} aria-label="Main navigation">
        <button onClick={()=>scrollTo('top')}>Home</button><button onClick={()=>scrollTo('services')}>Services</button><button onClick={()=>scrollTo('projects')}>Gallery</button><button onClick={()=>scrollTo('process')}>Process</button><button onClick={()=>scrollTo('contact')}>Contact</button>
      </nav>
      <button className="button dark nav-cta" onClick={()=>scrollTo('contact')}>Start a project <ArrowUpRight size={16}/></button>
    </header>

    <main id="top">
      <section className="hero section-pad">
        <div className="hero-copy reveal"><p className="eyebrow">Commercial interiors · Space planning · Turnkey delivery</p><h1>Designing better spaces for the way business works.</h1><p className="lead">AA Space Design creates considered workspaces through planning, interiors, partitions and coordinated project execution.</p><div className="hero-actions"><button className="button dark" onClick={()=>scrollTo('contact')}>Request a consultation <ArrowUpRight size={18}/></button><button className="text-link" onClick={()=>scrollTo('projects')}>Explore our work <ArrowDown size={17}/></button></div></div>
        <div className="hero-visual reveal"><div className="hero-image main-img"><img src={A+'1-27.webp'} alt="Bright contemporary interior with warm materials"/></div><div className="hero-image inset-img"><img src={A+'1-31.webp'} alt="Modern workspace interior"/></div><div className="frame-accent" aria-hidden="true"></div><div className="scroll-card" aria-hidden="true"><ArrowDown/></div></div>
      </section>

      <section className="stats section-pad"><div><strong>Commercial</strong><span>interior focus</span></div><div><strong>Turnkey</strong><span>from plan to handover</span></div><div><strong>Pakistan</strong><span>service area to confirm</span></div></section>

      <section id="services" className="services section-pad"><div className="section-heading"><span className="rule"></span><div><p className="eyebrow">What we do</p><h2>Integrated spaces, carefully delivered.</h2></div></div><div className="service-grid">{services.slice(0,3).map(([title,desc],i)=><article className="service-card" key={title}><span className="service-number">0{i+1}</span><h3>{title}</h3><p>{desc}</p><button className="circle-arrow" onClick={()=>setOpenService(i)} aria-label={`Open ${title} details`}><ArrowUpRight size={18}/></button></article>)}</div><div className="service-list">{services.slice(3).map(([title,desc],i)=><div className={'service-row '+(openService===i+3?'active':'')} key={title}><button onClick={()=>setOpenService(openService===i+3?-1:i+3)}><span>{title}</span>{openService===i+3?<ChevronDown/>:<ArrowUpRight/>}</button>{openService===i+3&&<p>{desc}</p>}</div>)}</div></section>

      <section id="projects" className="projects section-pad" aria-labelledby="projects-title"><div className="section-heading"><span className="rule"></span><div><p className="eyebrow">Projects / Gallery</p><h2 id="projects-title">Spaces designed around real requirements.</h2></div></div><div className="gallery-intro"><p>Explore selected interior visuals across commercial spaces, office environments and project details. Project names, locations and completion information will be added after the asset review.</p><span className="gallery-note">Draft gallery · project details to be confirmed</span></div><div className="gallery-filters" role="group" aria-label="Filter gallery projects">{filters.map(filter=><button key={filter} className={projectFilter===filter?'active':''} onClick={()=>setProjectFilter(filter)} aria-pressed={projectFilter===filter}>{filter}</button>)}</div><div className="gallery-grid">{visibleProjects.map((project,index)=><article className="gallery-card" key={`${project.category}-${index}`}><button className="gallery-image" onClick={()=>openProject(project)} aria-label={`Open ${project.label}`}><img src={A+project.images[0]} alt={project.alt} loading={index>1?'lazy':'eager'}/><span className="gallery-expand"><Expand size={17}/></span></button><div className="gallery-card-meta"><span>{project.category}</span><strong>{project.label}</strong><small>Project details to be confirmed</small><button className="gallery-view" onClick={()=>openProject(project)}>View project <ArrowUpRight size={15}/></button></div></article>)}</div><div className="gallery-footer"><p>Have a project to add? Send the location, project type, services and approved images.</p><button className="button dark" onClick={()=>scrollTo('contact')}>Submit project details <ArrowUpRight size={17}/></button></div></section>

      <section id="process" className="process section-pad"><div className="process-copy"><p className="eyebrow">Our approach</p><h2>From first brief to finished space.</h2><p className="lead">A simple, transparent process helps every decision stay connected to your goals, programme and site realities.</p><div className="steps">{process.map(([no,title,desc])=><div className="step" key={no}><span className="step-no">{no}</span><div><h3>{title}</h3><p>{desc}</p></div></div>)}</div></div><div className="process-image"><img src={A+'1-111.webp'} alt="Interior installation and finishing detail"/></div></section>

      <section className="quote-band section-pad"><div><p className="eyebrow">A stronger starting point</p><h2>Good spaces begin with good questions.</h2></div><button className="button light" onClick={()=>scrollTo('contact')}>Start a conversation <ArrowUpRight size={17}/></button></section>

      <section id="contact" className="contact section-pad"><div className="contact-copy"><p className="eyebrow">Let’s talk</p><h2>Tell us what your space needs next.</h2><p>Share a few details. We will use this first-draft form to shape a useful starting conversation.</p><div className="contact-note"><strong>Official contact details</strong><span>To be confirmed by Abdullah Afzal</span></div></div><form className="contact-form" onSubmit={e=>{e.preventDefault();setSubmitted(true)}}>{submitted?<div className="success"><Check size={28}/><h3>Thank you.</h3><p>Your enquiry is captured for this first draft. Official submission handling is to be connected.</p></div>:<><label>Name<input required placeholder="Your name"/></label><label>Company<input placeholder="Company name"/></label><label>Project details<textarea required rows="4" placeholder="City, space type, services or timeline"></textarea></label><button className="button dark" type="submit">Send enquiry <ArrowUpRight size={17}/></button></>}</form></section>
    </main>

    <footer className="footer"><div className="footer-brand"><img src={A+'aa-space-design-logo.png'} alt="AA Space Design"/><p>Thoughtful commercial interiors, space planning and coordinated project delivery.</p><div className="socials"><a href="#contact" aria-label="Instagram">IG</a><a href="#contact" aria-label="LinkedIn">in</a></div></div><div><h4>Explore</h4><button onClick={()=>scrollTo('services')}>Services</button><button onClick={()=>scrollTo('process')}>Process</button><button onClick={()=>scrollTo('projects')}>Projects</button></div><div><h4>Services</h4><span>Office partitioning</span><span>Commercial interiors</span><span>Turnkey projects</span></div><div><h4>Contact</h4><span>aaspacedesign.com</span><span>[Phone to confirm]</span><span>[City / address to confirm]</span></div></footer>

    {selectedProject&&<div className="project-lightbox" role="dialog" aria-modal="true" aria-label="Project detail" onClick={closeProject}><div className="project-lightbox-inner" onClick={event=>event.stopPropagation()}><div className="lightbox-header"><div><p className="eyebrow">PROJECTS / PROJECT DETAIL</p><span>AA Associate / Interior Hub</span></div><button className="lightbox-close-button" onClick={closeProject}>Close <X size={19}/></button></div><div className="project-presentation"><div className="project-imagery"><div className="conceptual-project-image"><img src={A+selectedProject.images[selectedImage]} alt={selectedProject.alt}/><div>Replace with approved project asset</div></div><div className="gallery-caption"><span>Conceptual sample imagery</span><span>{selectedImage+1} / {selectedProject.images.length}</span></div><div className="project-thumbnails">{selectedProject.images.map((image,index)=><button key={image} className={selectedImage===index?'selected':''} onClick={()=>setSelectedImage(index)} aria-label={`View image ${index+1}`}><img src={A+image} alt=""/><span>Replace with approved project asset</span></button>)}</div><p className="lightbox-disclaimer">Illustrations only, not a record of completed company work. Replace each image with an approved asset for this project.</p></div><aside className="project-details"><div className="project-title"><p className="eyebrow">PROJECT NAME</p><h3>To be confirmed</h3></div><div className="project-metadata"><div><span>City</span><strong>To be confirmed</strong></div><div><span>Category</span><strong>{selectedProject.category}</strong></div><div><span>Client</span><strong>To be confirmed</strong></div><div><span>Date</span><strong>To be confirmed</strong></div><div><span>Services</span><strong>To be confirmed</strong></div><div><span>Summary</span><strong>To be confirmed</strong></div></div><div className="similar-project"><h4>Have a space in mind?</h4><p>Share your requirements to discuss the design approach and the scope of services for your space.</p><button className="button red" onClick={()=>{closeProject();scrollTo('contact')}}>Discuss a Similar Project <ArrowUpRight size={16}/></button></div><p className="lightbox-disclaimer">Project information is awaiting confirmation. No client, location or completion claim is implied by the sample imagery.</p></aside></div></div></div>}
  </div>
}

createRoot(document.getElementById('root')).render(<App />)
