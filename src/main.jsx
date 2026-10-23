import React, { useEffect, useState } from 'react'
import { createRoot } from 'react-dom/client'
import { ArrowDown, ArrowUpRight, Check, ChevronDown, Expand, HardHat, LayoutGrid, Mail, MapPin, Menu, MessageCircle, PenTool, Phone, Play, X } from 'lucide-react'
import ServiceSection from './ServiceSection.jsx'
import './styles.css'

const A='/assets/'
const driveAsset=(name)=>A+'drive-gallery/selected/'+name

const services=[
  ['Space Planning','Functional layouts that make every square metre work harder for your people and your business.'],
  ['Office Partitioning','Glass, wood, aluminium and fabric partition systems with clean, practical detailing.'],
  ['Commercial Interiors','Workplaces designed around your brand, workflow, comfort and long-term use.'],
  ['Civil & Structural Work','Coordinated site preparation, renovation, flooring, ceilings and structural work.'],
  ['Electrical, Plumbing & HVAC','Essential building systems planned with the interior from day one.'],
  ['IT Infrastructure','Voice, data, power cabling and technology-ready workspaces with tidy management.'],
  ['Furniture Works','Custom joinery, glass installation and end-to-end project execution.'],
]

const videoCategories=[
  {id:'interior-fit-out',title:'Interior Fit-Out, Carpentry & Furniture Works',shortTitle:'Interior Fit-Out',poster:'drive-gallery/video-posters/interior-fit-out.jpg',accent:'terracotta',description:'Architecture, interior fit-out, carpentry, furniture and finishing work for homes, offices and commercial spaces.',scope:['Architecture and design','Residential and commercial interior design','Office partitioning systems','Carpentry, woodwork and joinery','Custom and loose furniture','Glass installation','Civil and structural work','Renovation and turnkey execution'],videos:[
    ['1HZ6wdmffP9gZIRFP5Ic7_EKtMWn3rXJM','Interior project preview 01'],['12NvHJjQShLHnuw5GBlETf3A1qFvGHklY','Interior project preview 02'],['1AoeoajYXCbuzeXaCqHbFNagaAo0k_glg','Interior project preview 03'],['1m7WGmrydrjGO_rVXWKH0-1iWBkxS5MJQ','Interior project preview 04']
  ]},
  {id:'mep-services',title:'MEP Services: Electrical, Plumbing & HVAC',shortTitle:'MEP Services',poster:'drive-gallery/video-posters/mep-services.jpg',accent:'sage',description:'Coordinated electrical, plumbing, air-conditioning and HVAC work for practical, functional spaces.',scope:['Electrical installation and coordination','Lighting and power solutions','Plumbing systems','Water supply and drainage coordination','Air-conditioning systems','HVAC installation and coordination','Maintenance access and service planning'],videos:[
    ['1pQh-5sudIr_JmM-PMutkEcRpvndEUIUp','MEP project preview 01'],['178EMG7uSAGD_0tvVK0tkkBypf8UcBHO1','MEP project preview 02'],['1IDQVw1skIqC4ZPk3dxgFUhDDnh2WkHKq','MEP project preview 03'],['1acQf4F5oC0vQNWQd0Hl0qRqiXrWHdc8A','MEP project preview 04']
  ]},
  {id:'it-infrastructure',title:'IT Infrastructure & Structured Cabling',shortTitle:'IT Infrastructure',poster:'drive-gallery/video-posters/it-infrastructure.jpg',accent:'blue',description:'Clean, organised voice, data and power cabling for residential, office and commercial project requirements.',scope:['IT infrastructure planning for project cabling','Voice cabling','Data cabling','Structured cabling solutions','Power and data cable management','Cable routing and organisation','Clean infrastructure integration'],videos:[
    ['100X1s26HfYwhvUJ3-jlDeApTe11pndqn','Structured cabling project preview']
  ]}
]
const driveVideo=(id)=>`https://drive.google.com/file/d/${id}/preview`
const driveThumbnail=(id)=>`https://drive.google.com/thumbnail?id=${id}&sz=w1200`

const process=[
  ['01','Discover','Share your brief, site information and priorities. We define the scope before design begins.'],
  ['02','Design','We translate requirements into a clear plan, coordinated drawings and material direction.'],
  ['03','Deliver','Our team coordinates installation, quality checks and handover with practical communication.'],
]

const projects=[
  {images:['office-partitioning.jpg'],category:'Office Partitioning',label:'Office partitioning',alt:'Office partitioning preview from the supplied project media',source:'02-Office-Partitioning'},
  {images:['commercial-interior.jpg'],category:'Commercial Interior Design',label:'Commercial interior design',alt:'Commercial interior preview from the supplied project media',source:'03-Commercial-Interior-Design'},
  {images:['civil-structural.jpg'],category:'Civil & Structural Work',label:'Civil and structural work',alt:'Civil and structural work preview from the supplied project media',source:'04-Civil-and-Structural-Work'},
  {images:['electrical-plumbing.jpg'],category:'Electrical & Plumbing',label:'Electrical and plumbing',alt:'Electrical and plumbing preview from the supplied project media',source:'05-Electrical-and-Plumbing'},
  {images:['hvac.jpg'],category:'HVAC & Air Conditioning',label:'HVAC and air conditioning',alt:'HVAC and air conditioning preview from the supplied project media',source:'06-HVAC-and-Air-Conditioning'},
  {images:[],category:'IT Infrastructure',label:'IT infrastructure',alt:'Approved IT infrastructure imagery to be added',source:'07-IT-Infrastructure'},
  {images:['carpentry.jpg','carpentry-detail.jpg'],category:'Carpentry & Woodwork',label:'Carpentry and woodwork',alt:'Carpentry and woodwork preview from the supplied project media',source:'08-Carpentry-and-Woodwork'},
  {images:['glass-installation.jpg'],category:'Glass Installation',label:'Glass installation',alt:'Glass installation preview from the supplied project media',source:'09-Glass-Installation'},
  {images:['turnkey-01.jpg','turnkey-02.jpg','turnkey-03.jpg'],category:'Full Turnkey Projects',label:'Full turnkey projects',alt:'Full turnkey project interior from the supplied project images',source:'10-Full-Turnkey-Projects'},
  {images:['before-during-after.jpg','before-during-after-frame.jpg'],category:'Before / During / After',label:'Project before, during and after',alt:'Project progress preview from the supplied project media',source:'11-Project-Before-During-After'},
]

function App(){
  const [menuOpen,setMenuOpen]=useState(false)
  const [scrolled,setScrolled]=useState(false)
  const [heroSlide,setHeroSlide]=useState(0)
  const [contactOpen,setContactOpen]=useState(false)
  const [openService,setOpenService]=useState(-1)
  const [projectFilter,setProjectFilter]=useState('All')
  const [projectsExpanded,setProjectsExpanded]=useState(false)
  const [selectedProject,setSelectedProject]=useState(null)
  const [selectedImage,setSelectedImage]=useState(0)
  const [submitted,setSubmitted]=useState(false)
  const [submitting,setSubmitting]=useState(false)
  const [formError,setFormError]=useState('')
  const [selectedVideoCategory,setSelectedVideoCategory]=useState(null)
  const [selectedVideo,setSelectedVideo]=useState(0)
  const [currentProjects,setCurrentProjects]=useState([])
  const [archiveProjects,setArchiveProjects]=useState([])
  const [archiveYears,setArchiveYears]=useState([])
  const [archiveMonths,setArchiveMonths]=useState([])
  const [archiveYear,setArchiveYear]=useState('')
  const [archiveMonth,setArchiveMonth]=useState('')
  const [archiveOpen,setArchiveOpen]=useState(false)
  const [archiveLoading,setArchiveLoading]=useState(false)
  const [archiveError,setArchiveError]=useState('')
  const [selectedDynamicProject,setSelectedDynamicProject]=useState(null)
  const [selectedDynamicMedia,setSelectedDynamicMedia]=useState(0)
  const scrollTo=(id)=>{ document.getElementById(id)?.scrollIntoView({behavior:'smooth'}); setMenuOpen(false) }
  const openProject=(project)=>{ setSelectedProject(project); setSelectedImage(0) }
  const closeProject=()=>{ setSelectedProject(null); setSelectedImage(0) }
  const openVideoGallery=(category)=>{ setSelectedVideoCategory(category); setSelectedVideo(0) }
  const closeVideoGallery=()=>{ setSelectedVideoCategory(null); setSelectedVideo(0) }
  const closeArchive=()=>setArchiveOpen(false)
  const openDynamicProject=(project)=>{setSelectedDynamicProject(project);setSelectedDynamicMedia(0)}
  const closeDynamicProject=()=>{setSelectedDynamicProject(null);setSelectedDynamicMedia(0)}
  const openContact=()=>{setContactOpen(true);setMenuOpen(false)}
  const closeContact=()=>setContactOpen(false)
  const driveEmbed=(url)=>url.includes('/file/d/')?url.replace(/\/view(?:\?.*)?$/,'/preview'):url
  const mediaFor=(project)=>project.media?.length?project.media:[...(project.image_url?[{type:'image',url:project.image_url,label:null}]:[]),...(project.embed_url?[{type:'drive',url:project.embed_url,label:null}]:[])]
  const imageFor=(project)=>mediaFor(project).find(media=>media.type==='image')?.url || ''
  const fetchProjects=async (year='',month='',limit=100)=>{
    const query=new URLSearchParams({limit:String(limit)}); if(year) query.set('year',year); if(month) query.set('month',month)
    const response=await fetch(`/api/projects.php?${query.toString()}`); const result=await response.json()
    if(!response.ok||!result.ok) throw new Error(result.message||'Project archive unavailable.')
    return result
  }
  const openArchive=async()=>{ setArchiveOpen(true); setArchiveLoading(true); setArchiveError(''); try{ const result=await fetchProjects(); setArchiveProjects(result.projects||[]); setArchiveYears(result.years||[]); setArchiveMonths(result.months||[]) }catch(error){ setArchiveError(error.message||'Project archive unavailable.') }finally{ setArchiveLoading(false) } }
  const filterArchive=async(year,month)=>{ setArchiveYear(year); setArchiveMonth(month); setArchiveLoading(true); try{ const result=await fetchProjects(year,month); setArchiveProjects(result.projects||[]) }catch(error){ setArchiveError(error.message||'Project archive unavailable.') }finally{ setArchiveLoading(false) } }
  useEffect(()=>{ const onKey=(event)=>{ if(event.key==='Escape'){ closeVideoGallery(); closeProject(); closeArchive(); closeDynamicProject(); closeContact() } }; window.addEventListener('keydown',onKey); return()=>window.removeEventListener('keydown',onKey) },[])
  useEffect(()=>{ const onScroll=()=>setScrolled(window.scrollY>12); onScroll(); window.addEventListener('scroll',onScroll,{passive:true}); return()=>window.removeEventListener('scroll',onScroll) },[])
  useEffect(()=>{ fetchProjects('', '', 100).then(result=>setCurrentProjects(result.projects||[])).catch(()=>setCurrentProjects([])) },[])
  useEffect(()=>{ if(window.matchMedia('(prefers-reduced-motion: reduce)').matches) return; const timer=window.setInterval(()=>setHeroSlide(slide=>(slide+1)%3),6500); return()=>window.clearInterval(timer) },[])
  const clientNames=['Move n Pick Hotel','Sanober Yasir Salon','Bank Islami','Classic Ink','Uniferoz','Efroze Pharma','Askari Life Insurance','United Gypxum','Marriot Hotels & Resots','Ambiace Boutique Art Hotel','Reko Diq Minig Company','Al Fauz Travel Agency','the Owis Nest Resturant','Karachi Paint House','Hongshao Resturant','Artibe BPO','Raqami Islamic Digital bank','Lucky Cement','Fecto Cement','Efro Tech',"Gerry's Danata",'International Brand Limited','BMS Associates','& more then 300+ Residential Project']
  const clientRows=[clientNames.slice(0,8),clientNames.slice(8,16),clientNames.slice(16)]
  const heroSlides=[{number:'01',title:'Custom sofas, made for your space.',description:'From first sketch to final stitch, we make sofas and furniture that belong in the room.',image:'drive-gallery/selected/carpentry-detail.jpg',alt:'Crafted seating and joinery detail'},{number:'02',title:'Furniture that completes the interior.',description:'A coordinated approach to sofas, woodwork, partitions and the details around them.',image:'drive-gallery/selected/commercial-interior.jpg',alt:'Commercial interior with furniture work'},{number:'03',title:'Interior work with a furniture-first eye.',description:'Thoughtful planning, reliable execution and custom pieces for homes, offices and commercial spaces.',image:'drive-gallery/selected/turnkey-01.jpg',alt:'Turnkey interior project'}]
  const filters=['All',...new Set(projects.map(project=>project.category))]
  const visibleProjects=projects.filter(project=>projectFilter==='All'||project.category===projectFilter)
  return <div className="site">
    <header className={scrolled?'nav-wrap scrolled':'nav-wrap'}><div className="header-main"><div className="brand-lockup"><a className="brand" href="#top" aria-label="AA Associate home"><img src={A+'aa-associate-logo-new-j.png'} alt="AA Associate logo" /></a></div><nav className={menuOpen?'nav open':'nav'} aria-label="Main navigation"><button onClick={()=>scrollTo('top')}>Home</button><button onClick={()=>scrollTo('services')}>Service</button><button onClick={()=>scrollTo('projects')}>Projects</button><button onClick={()=>scrollTo('about')}>About</button><button onClick={()=>openContact()}>Contact</button></nav><div className="header-actions"><a className="header-whatsapp" href="https://wa.me/923009233163" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp"><MessageCircle size={19}/></a><button className="header-quote" onClick={()=>openContact()}>Get a Quote <ArrowUpRight size={15}/></button></div><button className="menu-toggle" onClick={()=>setMenuOpen(!menuOpen)} aria-label={menuOpen?'Close menu':'Open menu'}>{menuOpen?<X/>:<Menu/>}</button></div></header>
    <main id="top" className="preview-homepage">
      <section className="hero hero-slider section-pad" style={{'--hero-poster':`url(${A}media/aa-associate-hero-poster.jpg)`}} aria-labelledby="hero-title">
        <div className="hero-copy reveal">
          <p className="eyebrow">AA Associate</p>
          <span className="hero-slide-number">{heroSlides[heroSlide].number} / 03</span>
          <h1 id="hero-title">{heroSlides[heroSlide].title}</h1>
          <p className="lead">{heroSlides[heroSlide].description}</p>
          <div className="hero-actions"><button className="button dark" onClick={()=>scrollTo('projects')}>See more works <ArrowUpRight size={18}/></button><button className="text-link" onClick={()=>openContact()}>Start a project <ArrowUpRight size={18}/></button></div>
          <div className="hero-controls" aria-label="Hero slides">{heroSlides.map((slide,index)=><button key={slide.number} className={heroSlide===index?'active':''} onClick={()=>setHeroSlide(index)} aria-label={`Show slide ${index+1}`}><span>{slide.number}</span></button>)}</div>
        </div>
        <div className="hero-background-media" aria-hidden="true"><video autoPlay={!window.matchMedia('(prefers-reduced-motion: reduce)').matches} muted loop playsInline preload={window.matchMedia('(prefers-reduced-motion: reduce)').matches?'none':'metadata'} poster={A+'media/aa-associate-hero-poster.jpg'}><source src={A+'media/aa-associate-hero.webm'} type="video/webm"/><source src={A+'media/aa-associate-hero.mp4'} type="video/mp4"/></video></div>
      </section>
      <section id="about" className="who-we-are section-pad" aria-labelledby="who-we-are-title"><div className="who-we-are-label"><span className="rule"></span><p className="eyebrow">WHO WE ARE</p><span className="who-we-are-location">Karachi · Pakistan</span></div><div className="who-we-are-content"><h2 id="who-we-are-title">Furniture first. Interiors made around you.</h2><p>AA Associate makes custom sofas and furniture, then carries that craft into home, office and commercial interiors—from planning and joinery through to installation.</p><button className="text-link" onClick={()=>scrollTo('services')}>Explore our services <ArrowUpRight size={16}/></button></div></section><section className="trust-strip section-pad compact-trust-strip"><p>Built around your brief</p><div><span>Custom sofas</span><span>Furniture making</span><span>Interior execution</span></div></section>
      <ServiceSection onContact={openContact}/>

      <section id="projects" className={`projects section-pad${projectsExpanded ? ' is-expanded' : ''}`} aria-labelledby="projects-title">
        <div className="projects-disclosure-bar">
          <div className="section-heading">
            <span className="rule"></span>
            <div><p className="eyebrow">Selected work</p><h2 id="projects-title">See the work in context.</h2></div>
          </div>
          <button type="button" className="projects-disclosure-button" aria-expanded={projectsExpanded} aria-controls="projects-content" onClick={() => setProjectsExpanded((expanded) => !expanded)}>
            <span>{projectsExpanded ? 'Hide projects' : 'View projects'}</span>
            <span className="projects-disclosure-icon" aria-hidden="true">{projectsExpanded ? '−' : '+'}</span>
          </button>
        </div>
        <div id="projects-content" className="projects-content" hidden={!projectsExpanded}>
          <div className="projects-intro-row">
            <p>Furniture, fit-out and interior details presented together. Select a project to view its supplied images.</p>
            <button className="text-link" onClick={() => openContact()}>Discuss your space <ArrowUpRight size={16}/></button>
          </div>
          <div className="gallery-grid preview-gallery">
            {projects.slice(0, 6).map((project, index) => (
              <article className="gallery-card" key={project.category}>
                <button className="gallery-image" onClick={() => project.images.length && openProject(project)} disabled={!project.images.length}>
                  {project.images.length ? <img src={driveAsset(project.images[0])} alt={project.alt} loading="lazy"/> : <div className="gallery-placeholder">Image coming soon</div>}
                  <span className="gallery-hover-label">View project <ArrowUpRight size={16}/></span>
                </button>
                <div className="gallery-card-meta"><span>0{index + 1} / Selected work</span><strong>{project.label}</strong></div>
              </article>
            ))}
          </div>
          <div className="gallery-footer"><p>More projects are being added to the archive.</p><button className="button dark" onClick={openContact}>Start a project <ArrowUpRight size={17}/></button></div>
        </div>
      </section>
      <section className="client-marquee-section" aria-label="Selected clients"><div className="client-marquee-heading"><span className="eyebrow">Selected clients</span><span>Spaces we have helped shape</span></div><div className="client-rows">{clientRows.map((row,rowIndex)=><div className={`client-row client-row-${rowIndex+1}`} key={rowIndex}><div className="client-row-track">{[...row,...row].map((name,index)=><span key={`${name}-${index}`} aria-hidden={index>=row.length}>{name}</span>)}</div></div>)}</div></section>
      <section className="contact-trigger section-pad" aria-labelledby="contact-trigger-title"><div><p className="eyebrow">Start a project</p><h2 id="contact-trigger-title">Have a space in mind?</h2></div><button className="button dark" onClick={openContact}>Open contact form <ArrowUpRight size={17}/></button></section>
      {contactOpen&&<div className="contact-modal" role="dialog" aria-modal="true" aria-labelledby="contact-modal-title" onClick={closeContact}><div className="contact-modal-shell" onClick={event=>event.stopPropagation()}><div className="contact-modal-header"><div><p className="eyebrow">AA Associate / Contact</p><h2 id="contact-modal-title">Tell us what your space needs next.</h2></div><button className="lightbox-close-button" onClick={closeContact}>Close <X size={19}/></button></div><div className="contact contact-modal-content"><div className="contact-copy"><p className="eyebrow">Let’s talk</p><h2>Share your project details.</h2><p>Share a few details. We will use this first-draft form to shape a useful starting conversation.</p><div className="contact-channels"><a className="contact-channel" href="tel:+923009233163"><span className="contact-channel-icon"><Phone size={19}/></span><span className="contact-channel-body"><span className="contact-channel-label">Call us</span><span className="contact-channel-value">+92 300 9233163</span></span></a><a className="contact-channel" href="https://wa.me/923009233163" target="_blank" rel="noopener noreferrer"><span className="contact-channel-icon"><MessageCircle size={19}/></span><span className="contact-channel-body"><span className="contact-channel-label">WhatsApp</span><span className="contact-channel-value">Message our team</span></span></a><a className="contact-channel" href="mailto:info@aaspacedesign.com"><span className="contact-channel-icon"><Mail size={19}/></span><span className="contact-channel-body"><span className="contact-channel-label">Email</span><span className="contact-channel-value">info@aaspacedesign.com</span></span></a></div><div className="contact-area"><MapPin size={15}/><span>Karachi · Islamabad · Lahore · all over Pakistan</span></div></div><form className="contact-form" onSubmit={async e=>{e.preventDefault();setSubmitting(true);setFormError('');const form=e.currentTarget;const payload=Object.fromEntries(new FormData(form).entries());try{const response=await fetch('/api/submit-enquiry.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});const result=await response.json();if(!response.ok||!result.ok)throw new Error(result.message||'Submission failed.');setSubmitted(true);form.reset()}catch(error){setFormError(error.message||'We could not submit your enquiry. Please try again.')}finally{setSubmitting(false)}}}>{submitted?<div className="success"><Check size={28}/><h3>Thank you.</h3><p>Your enquiry has been received. Our team will contact you shortly.</p></div>:<><label>Name<input name="name" required autoComplete="name" placeholder="Your name"/></label><label>Email<input name="email" type="email" required autoComplete="email" placeholder="you@example.com"/></label><label>Phone / WhatsApp<input name="phone_whatsapp" type="tel" required autoComplete="tel" placeholder="+92 3XX XXXXXXX"/></label><label>Project type<select name="project_type" required defaultValue=""><option value="" disabled>Select project type</option><option>Residential</option><option>Commercial</option><option>Renovation</option></select></label><label>City<input name="city" required autoComplete="address-level2" placeholder="Your city"/></label><label>Budget range<input name="budget_range" placeholder="e.g. PKR 1,000,000"/></label><label>Preferred call date<input name="preferred_call_date" type="date" min={new Date().toISOString().split('T')[0]}/></label><label>Preferred call time<input name="preferred_call_time" type="time"/></label><label className="full-field contact-message-field">Message<textarea name="message" required rows="4" placeholder="Tell us about your space, requirements, and preferred timeline"></textarea></label><input name="website" className="form-honeypot" tabIndex="-1" autoComplete="off" aria-hidden="true"/><div className="form-submit full-field">{formError&&<p className="form-error" role="alert">{formError}</p>}<button className="button dark" type="submit" disabled={submitting}>{submitting?'Sending…':'Send enquiry'} <ArrowUpRight size={17}/></button></div></>}</form></div></div></div>}
    </main>    <footer className="footer footer-compact"><div className="footer-compact-brand"><img src={A+'aa-associate-logo-new-j.png'} alt="AA Associate logo"/></div><nav aria-label="Footer navigation"><button onClick={()=>scrollTo('top')}>Home</button><button onClick={()=>scrollTo('projects')}>Projects</button><button onClick={()=>scrollTo('about')}>About</button><button onClick={openContact}>Contact</button></nav><div className="footer-compact-contact"><a href="tel:+923009233163">Call</a><a href="https://wa.me/923009233163" target="_blank" rel="noopener noreferrer">WhatsApp</a><a href="mailto:info@aaspacedesign.com">Email</a></div><small>© AA Associate</small></footer>

    {archiveOpen&&<div className="archive-lightbox" role="dialog" aria-modal="true" aria-labelledby="archive-title" onClick={closeArchive}><div className="archive-panel" onClick={event=>event.stopPropagation()}><div className="archive-header"><div><p className="eyebrow">PROJECTS / ARCHIVE</p><h2 id="archive-title">Every project, properly recorded.</h2><p>Filter approved project entries by year and month.</p></div><button className="lightbox-close-button" onClick={closeArchive}>Close <X size={19}/></button></div><div className="archive-filters"><label>Year<select value={archiveYear} onChange={event=>filterArchive(event.target.value,archiveMonth)}><option value="">All years</option>{archiveYears.map(year=><option key={year} value={year}>{year}</option>)}</select></label><label>Month<select value={archiveMonth} onChange={event=>filterArchive(archiveYear,event.target.value)}><option value="">All months</option>{archiveMonths.map(month=><option key={month} value={month}>{new Date(2000,month-1,1).toLocaleString('en',{month:'long'})}</option>)}</select></label></div>{archiveLoading?<p className="archive-status">Loading project archive…</p>:archiveError?<p className="archive-status archive-error">{archiveError}</p>:archiveProjects.length?<div className="archive-grid">{archiveProjects.map(project=><article className="archive-card" key={project.id}>{imageFor(project)?<img src={imageFor(project)} alt={project.title} loading="lazy"/>:<div className="archive-placeholder"><strong>{String(project.year)}</strong><span>Project file</span></div>}<div><span>{new Date(2000,project.month-1,1).toLocaleString('en',{month:'short'})} {project.year}</span><h3>{project.title}</h3><p>{project.description}</p><div className="project-media-links">{mediaFor(project).map((media,index)=><button type="button" onClick={()=>openDynamicProject(project)} key={`${media.url}-${index}`}>{media.type==='drive'?'View video':'View image'} <ArrowUpRight size={14}/></button>)}</div></div></article>)}</div>:<p className="archive-status">No published projects match selected filters.</p>}</div></div>}
    {selectedDynamicProject&&<div className="dynamic-project-lightbox" role="dialog" aria-modal="true" aria-labelledby="dynamic-project-title" onClick={closeDynamicProject}><div className="dynamic-project-panel" onClick={event=>event.stopPropagation()}><div className="dynamic-project-header"><div><p className="eyebrow">PROJECT MEDIA / ARCHIVE</p><h2 id="dynamic-project-title">{selectedDynamicProject.title}</h2><p>{String(selectedDynamicProject.month).padStart(2,'0')} / {selectedDynamicProject.year}</p></div><button className="lightbox-close-button" onClick={closeDynamicProject}>Close <X size={19}/></button></div><div className="dynamic-project-body"><div className="dynamic-project-viewer">{mediaFor(selectedDynamicProject)[selectedDynamicMedia]?.type==='drive'?<iframe src={driveEmbed(mediaFor(selectedDynamicProject)[selectedDynamicMedia].url)} title={mediaFor(selectedDynamicProject)[selectedDynamicMedia].label||'Project video'} allow="autoplay; fullscreen" allowFullScreen referrerPolicy="strict-origin-when-cross-origin"/>:<img src={mediaFor(selectedDynamicProject)[selectedDynamicMedia]?.url} alt={mediaFor(selectedDynamicProject)[selectedDynamicMedia]?.label||selectedDynamicProject.title}/>}</div><div className="dynamic-project-caption"><span>{mediaFor(selectedDynamicProject)[selectedDynamicMedia]?.label||'Project media'}</span><span>{selectedDynamicMedia+1} / {mediaFor(selectedDynamicProject).length}</span></div><div className="dynamic-project-thumbnails">{mediaFor(selectedDynamicProject).map((media,index)=><button type="button" className={selectedDynamicMedia===index?'selected':''} onClick={()=>setSelectedDynamicMedia(index)} key={`${media.url}-${index}`}><span>{media.type==='drive'?'VIDEO':'IMAGE'}</span><small>{media.label||`Media ${index+1}`}</small></button>)}</div></div><aside className="dynamic-project-details"><p className="eyebrow">PROJECT DETAILS</p><h3>{selectedDynamicProject.title}</h3><p>{selectedDynamicProject.description}</p><div className="dynamic-project-meta"><span>Year <strong>{selectedDynamicProject.year}</strong></span><span>Month <strong>{new Date(2000,selectedDynamicProject.month-1,1).toLocaleString('en',{month:'long'})}</strong></span><span>Media <strong>{mediaFor(selectedDynamicProject).length} files</strong></span></div></aside></div></div>}
    {selectedVideoCategory&&<div className="drive-video-lightbox" role="dialog" aria-modal="true" aria-labelledby="drive-video-title" onClick={closeVideoGallery}><div className="drive-video-panel" onClick={event=>event.stopPropagation()}><div className="drive-video-header"><div><p className="eyebrow">VIDEO GALLERY / GOOGLE DRIVE</p><h2 id="drive-video-title">{selectedVideoCategory.title}</h2><p>{selectedVideoCategory.description}</p></div><button className="lightbox-close-button" onClick={closeVideoGallery}>Close <X size={19}/></button></div><div className="drive-video-scope">{selectedVideoCategory.scope.map(item=><span key={item}>{item}</span>)}</div><div className="drive-video-viewer"><iframe src={driveVideo(selectedVideoCategory.videos[selectedVideo][0])} title={selectedVideoCategory.videos[selectedVideo][1]} allow="autoplay; fullscreen" allowFullScreen referrerPolicy="strict-origin-when-cross-origin"></iframe><div className="drive-video-caption"><span>{selectedVideoCategory.videos[selectedVideo][1]}</span><span>{selectedVideo+1} / {selectedVideoCategory.videos.length}</span></div></div><div className="drive-video-thumbnails">{selectedVideoCategory.videos.map(([id,title],index)=><button key={id} className={selectedVideo===index?'selected':''} onClick={()=>setSelectedVideo(index)} aria-label={`Play ${title}`}><img src={driveThumbnail(id)} alt="" loading="lazy"/><span className="drive-thumb-play" aria-hidden="true"><Play size={14} fill="currentColor"/></span><span>{title}</span></button>)}</div></div></div>}

    {selectedProject&&<div className="project-lightbox" role="dialog" aria-modal="true" aria-label="Project detail" onClick={closeProject}><div className="project-lightbox-inner" onClick={event=>event.stopPropagation()}><div className="lightbox-header"><div><p className="eyebrow">PROJECTS / PROJECT DETAIL</p><span>AA Associate</span></div><button className="lightbox-close-button" onClick={closeProject}>Close <X size={19}/></button></div><div className="project-presentation"><div className="project-imagery"><div className="conceptual-project-image"><img src={driveAsset(selectedProject.images[selectedImage])} alt={selectedProject.alt}/><div>Selected from {selectedProject.source}</div></div><div className="gallery-caption"><span>Supplied Drive project media</span><span>{selectedImage+1} / {selectedProject.images.length}</span></div><div className="project-thumbnails">{selectedProject.images.map((image,index)=><button key={image} className={selectedImage===index?'selected':''} onClick={()=>setSelectedImage(index)} aria-label={`View image ${index+1}`}><img src={driveAsset(image)} alt=""/><span>View supplied asset</span></button>)}</div><p className="lightbox-disclaimer">Project information is awaiting confirmation. Images are sourced from the supplied Drive folder and are not presented with an unverified client, location or completion claim.</p></div><aside className="project-details"><div className="project-title"><p className="eyebrow">PROJECT NAME</p><h3>To be confirmed</h3></div><div className="project-metadata"><div><span>City</span><strong>To be confirmed</strong></div><div><span>Category</span><strong>{selectedProject.category}</strong></div><div><span>Client</span><strong>To be confirmed</strong></div><div><span>Date</span><strong>To be confirmed</strong></div><div><span>Services</span><strong>{selectedProject.category}</strong></div><div><span>Source folder</span><strong>{selectedProject.source}</strong></div></div><div className="similar-project"><h4>Have a space in mind?</h4><p>Share your requirements to discuss the design approach and the scope of services for your space.</p><button className="button red" onClick={()=>{closeProject();openContact()}}>Discuss a Similar Project <ArrowUpRight size={16}/></button></div><p className="lightbox-disclaimer">Project information is awaiting confirmation. No client, location or completion claim is implied by the supplied media.</p></aside></div></div></div>}
  </div>
}

createRoot(document.getElementById('root')).render(<App />)
