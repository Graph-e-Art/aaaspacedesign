import React, { useEffect, useState } from 'react'
import { createRoot } from 'react-dom/client'
import { ArrowDown, ArrowUpRight, Check, ChevronDown, Expand, Mail, MapPin, Menu, MessageCircle, Phone, Play, X } from 'lucide-react'
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
  ['Carpentry, Glass & Turnkey','Custom joinery, glass installation and end-to-end project execution.'],
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
  const [openService,setOpenService]=useState(0)
  const [projectFilter,setProjectFilter]=useState('All')
  const [selectedProject,setSelectedProject]=useState(null)
  const [selectedImage,setSelectedImage]=useState(0)
  const [submitted,setSubmitted]=useState(false)
  const [selectedVideoCategory,setSelectedVideoCategory]=useState(null)
  const [selectedVideo,setSelectedVideo]=useState(0)
  const scrollTo=(id)=>{ document.getElementById(id)?.scrollIntoView({behavior:'smooth'}); setMenuOpen(false) }
  const openProject=(project)=>{ setSelectedProject(project); setSelectedImage(0) }
  const closeProject=()=>{ setSelectedProject(null); setSelectedImage(0) }
  const openVideoGallery=(category)=>{ setSelectedVideoCategory(category); setSelectedVideo(0) }
  const closeVideoGallery=()=>{ setSelectedVideoCategory(null); setSelectedVideo(0) }
  useEffect(()=>{ const onKey=(event)=>{ if(event.key==='Escape'){ closeVideoGallery(); closeProject() } }; window.addEventListener('keydown',onKey); return()=>window.removeEventListener('keydown',onKey) },[])
  const filters=['All',...new Set(projects.map(project=>project.category))]
  const visibleProjects=projects.filter(project=>projectFilter==='All'||project.category===projectFilter)
  return <div className="site">
    <header className="nav-wrap">
      <a className="brand" href="#top" aria-label="AA Space Design home"><img src={A+'aa-space-design-logo.svg'} alt="AA Space Design" /></a>
      <button className="menu-toggle" onClick={()=>setMenuOpen(!menuOpen)} aria-label={menuOpen?'Close menu':'Open menu'}>{menuOpen?<X/>:<Menu/>}</button>
      <nav className={menuOpen?'nav open':'nav'} aria-label="Main navigation">
        <button onClick={()=>scrollTo('top')}>Home</button><button onClick={()=>scrollTo('services')}>Services</button><button onClick={()=>scrollTo('video-gallery')}>Gallery</button><button onClick={()=>scrollTo('contact')}>Contact</button>
      </nav>
    </header>

    <main id="top">
      <section className="hero section-pad">
        <div className="hero-copy reveal"><p className="eyebrow">Commercial interiors · Space planning · Turnkey delivery</p><h1>Designing better spaces for the way business works.</h1><p className="lead">AA Space Design creates considered workspaces through planning, interiors, partitions and coordinated project execution.</p><div className="hero-actions"><button className="button dark" onClick={()=>scrollTo('contact')}>Request a consultation <ArrowUpRight size={18}/></button></div></div>
        <div className="hero-visual reveal"><div className="hero-image main-img"><img src={A+'1-27.webp'} alt="Bright contemporary interior with warm materials"/></div><div className="hero-image inset-img"><img src={A+'1-31.webp'} alt="Modern workspace interior"/></div><div className="frame-accent" aria-hidden="true"></div><div className="scroll-card" aria-hidden="true"><ArrowDown/></div></div>
      </section>

      <section className="stats section-pad"><div><strong>Commercial</strong><span>interior focus</span></div><div><strong>Turnkey</strong><span>from plan to handover</span></div><div><strong>Pakistan</strong><span>Karachi · Islamabad · Lahore · all over Pakistan</span></div></section>

      <section id="services" className="services section-pad"><div className="section-heading"><span className="rule"></span><div><p className="eyebrow">What we do</p><h2>Integrated spaces, carefully delivered.</h2></div></div><div className="service-grid">{services.slice(0,3).map(([title,desc],i)=><article className="service-card" key={title}><span className="service-number">0{i+1}</span><h3>{title}</h3><p>{desc}</p><button className="circle-arrow" onClick={()=>setOpenService(i)} aria-label={`Open ${title} details`}><ArrowUpRight size={18}/></button></article>)}</div><div className="service-list">{services.slice(3).map(([title,desc],i)=><div className={'service-row '+(openService===i+3?'active':'')} key={title}><button onClick={()=>setOpenService(openService===i+3?-1:i+3)}><span>{title}</span>{openService===i+3?<ChevronDown/>:<ArrowUpRight/>}</button>{openService===i+3&&<p>{desc}</p>}</div>)}</div></section>

      <section id="video-gallery" className="video-gallery section-pad" aria-labelledby="video-gallery-title"><div className="section-heading"><span className="rule"></span><div><p className="eyebrow">Video gallery / Google Drive</p><h2 id="video-gallery-title">See the work inside each scope.</h2></div></div><div className="video-gallery-intro"><p>Explore approved project videos grouped into the three core service scopes. Videos stream directly from the supplied Google Drive folders.</p><span className="gallery-note">Google Drive player · 9 videos</span></div><div className="video-category-grid">{videoCategories.map(category=><article className={`video-category-card ${category.accent}`} key={category.id}><div className="video-card-preview"><img src={A+category.poster} alt={`${category.title} video preview`} loading="lazy"/><span className="drive-play" aria-hidden="true"><Play size={22} fill="currentColor"/></span><button className="video-card-open" onClick={()=>openVideoGallery(category)} aria-label={`Open ${category.title} video gallery`}><span>Open video gallery</span><ArrowUpRight size={17}/></button></div><div className="video-category-meta"><span className="video-category-kicker">{category.shortTitle}</span><h3>{category.title}</h3><p>{category.description}</p><ul>{category.scope.slice(0,5).map(item=><li key={item}>{item}</li>)}</ul><button className="video-category-link" onClick={()=>openVideoGallery(category)}>View all {category.videos.length} videos <ArrowUpRight size={15}/></button></div></article>)}</div></section>

      <section id="process" className="process section-pad"><div className="process-copy"><p className="eyebrow">Our approach</p><h2>From first brief to finished space.</h2><p className="lead">A simple, transparent process helps every decision stay connected to your goals, programme and site realities.</p><div className="steps">{process.map(([no,title,desc])=><div className="step" key={no}><span className="step-no">{no}</span><div><h3>{title}</h3><p>{desc}</p></div></div>)}</div></div><div className="process-image"><img src={A+'1-111.webp'} alt="Interior installation and finishing detail"/></div></section>

      <section className="quote-band section-pad"><div><p className="eyebrow">A stronger starting point</p><h2>Good spaces begin with good questions.</h2></div><button className="button light" onClick={()=>scrollTo('contact')}>Start a conversation <ArrowUpRight size={17}/></button></section>

      <section id="contact" className="contact section-pad"><div className="contact-copy"><p className="eyebrow">Let’s talk</p><h2>Tell us what your space needs next.</h2><p>Share a few details. We will use this first-draft form to shape a useful starting conversation.</p><div className="contact-channels"><a className="contact-channel" href="tel:+923009233163"><span className="contact-channel-icon"><Phone size={19}/></span><span className="contact-channel-body"><span className="contact-channel-label">Call us</span><span className="contact-channel-value">+92 300 9233163</span></span></a><a className="contact-channel" href="https://wa.me/923009233163" target="_blank" rel="noopener noreferrer"><span className="contact-channel-icon"><MessageCircle size={19}/></span><span className="contact-channel-body"><span className="contact-channel-label">WhatsApp</span><span className="contact-channel-value">Message our team</span></span></a><a className="contact-channel" href="mailto:info@aaspacedesign.com"><span className="contact-channel-icon"><Mail size={19}/></span><span className="contact-channel-body"><span className="contact-channel-label">Email</span><span className="contact-channel-value">info@aaspacedesign.com</span></span></a></div><div className="contact-area"><MapPin size={15}/><span>Karachi · Islamabad · Lahore · all over Pakistan</span></div></div><form className="contact-form" onSubmit={e=>{e.preventDefault();setSubmitted(true)}}>{submitted?<div className="success"><Check size={28}/><h3>Thank you.</h3><p>Your enquiry is captured for this first draft. Official submission handling is to be connected.</p></div>:<><label>Name<input required placeholder="Your name"/></label><label>Company<input placeholder="Company name"/></label><label>Project details<textarea required rows="4" placeholder="City, space type, services or timeline"></textarea></label><button className="button dark" type="submit">Send enquiry <ArrowUpRight size={17}/></button></>}</form></section>
    </main>

    <footer className="footer"><div className="footer-brand"><img src={A+'aa-space-design-logo.svg'} alt="AA Space Design"/><p>Thoughtful commercial interiors, space planning and coordinated project delivery.</p><div className="socials"><a href="#contact" aria-label="Instagram">IG</a><a href="#contact" aria-label="LinkedIn">in</a></div></div><div><h4>Explore</h4><button onClick={()=>scrollTo('services')}>Services</button><button onClick={()=>scrollTo('process')}>Process</button></div><div><h4>Services</h4><span>Office partitioning</span><span>Commercial interiors</span><span>Turnkey projects</span></div><div><h4>Contact</h4><div className="footer-contact"><a href="tel:+923009233163"><Phone size={13}/>+92 300 9233163</a><a href="https://wa.me/923009233163" target="_blank" rel="noopener noreferrer"><MessageCircle size={13}/>WhatsApp</a><a href="mailto:info@aaspacedesign.com"><Mail size={13}/>info@aaspacedesign.com</a><span><MapPin size={13}/>Karachi · Islamabad · Lahore</span></div></div></footer>

    {selectedVideoCategory&&<div className="drive-video-lightbox" role="dialog" aria-modal="true" aria-labelledby="drive-video-title" onClick={closeVideoGallery}><div className="drive-video-panel" onClick={event=>event.stopPropagation()}><div className="drive-video-header"><div><p className="eyebrow">VIDEO GALLERY / GOOGLE DRIVE</p><h2 id="drive-video-title">{selectedVideoCategory.title}</h2><p>{selectedVideoCategory.description}</p></div><button className="lightbox-close-button" onClick={closeVideoGallery}>Close <X size={19}/></button></div><div className="drive-video-scope">{selectedVideoCategory.scope.map(item=><span key={item}>{item}</span>)}</div><div className="drive-video-viewer"><iframe src={driveVideo(selectedVideoCategory.videos[selectedVideo][0])} title={selectedVideoCategory.videos[selectedVideo][1]} allow="autoplay; fullscreen" allowFullScreen referrerPolicy="strict-origin-when-cross-origin"></iframe><div className="drive-video-caption"><span>{selectedVideoCategory.videos[selectedVideo][1]}</span><span>{selectedVideo+1} / {selectedVideoCategory.videos.length}</span></div></div><div className="drive-video-thumbnails">{selectedVideoCategory.videos.map(([id,title],index)=><button key={id} className={selectedVideo===index?'selected':''} onClick={()=>setSelectedVideo(index)} aria-label={`Play ${title}`}><img src={driveThumbnail(id)} alt="" loading="lazy"/><span className="drive-thumb-play" aria-hidden="true"><Play size={14} fill="currentColor"/></span><span>{title}</span></button>)}</div></div></div>}

    {selectedProject&&<div className="project-lightbox" role="dialog" aria-modal="true" aria-label="Project detail" onClick={closeProject}><div className="project-lightbox-inner" onClick={event=>event.stopPropagation()}><div className="lightbox-header"><div><p className="eyebrow">PROJECTS / PROJECT DETAIL</p><span>AA Associate / Interior Hub</span></div><button className="lightbox-close-button" onClick={closeProject}>Close <X size={19}/></button></div><div className="project-presentation"><div className="project-imagery"><div className="conceptual-project-image"><img src={driveAsset(selectedProject.images[selectedImage])} alt={selectedProject.alt}/><div>Selected from {selectedProject.source}</div></div><div className="gallery-caption"><span>Supplied Drive project media</span><span>{selectedImage+1} / {selectedProject.images.length}</span></div><div className="project-thumbnails">{selectedProject.images.map((image,index)=><button key={image} className={selectedImage===index?'selected':''} onClick={()=>setSelectedImage(index)} aria-label={`View image ${index+1}`}><img src={driveAsset(image)} alt=""/><span>View supplied asset</span></button>)}</div><p className="lightbox-disclaimer">Project information is awaiting confirmation. Images are sourced from the supplied Drive folder and are not presented with an unverified client, location or completion claim.</p></div><aside className="project-details"><div className="project-title"><p className="eyebrow">PROJECT NAME</p><h3>To be confirmed</h3></div><div className="project-metadata"><div><span>City</span><strong>To be confirmed</strong></div><div><span>Category</span><strong>{selectedProject.category}</strong></div><div><span>Client</span><strong>To be confirmed</strong></div><div><span>Date</span><strong>To be confirmed</strong></div><div><span>Services</span><strong>{selectedProject.category}</strong></div><div><span>Source folder</span><strong>{selectedProject.source}</strong></div></div><div className="similar-project"><h4>Have a space in mind?</h4><p>Share your requirements to discuss the design approach and the scope of services for your space.</p><button className="button red" onClick={()=>{closeProject();scrollTo('contact')}}>Discuss a Similar Project <ArrowUpRight size={16}/></button></div><p className="lightbox-disclaimer">Project information is awaiting confirmation. No client, location or completion claim is implied by the supplied media.</p></aside></div></div></div>}
  </div>
}

createRoot(document.getElementById('root')).render(<App />)
