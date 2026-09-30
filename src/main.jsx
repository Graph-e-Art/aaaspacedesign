import React, { useState } from 'react'
import { createRoot } from 'react-dom/client'
import { ArrowDown, ArrowUpRight, Check, ChevronDown, Menu, X } from 'lucide-react'
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

function App(){
  const [menuOpen,setMenuOpen]=useState(false)
  const [openService,setOpenService]=useState(0)
  const [submitted,setSubmitted]=useState(false)
  const scrollTo=(id)=>{ document.getElementById(id)?.scrollIntoView({behavior:'smooth'}); setMenuOpen(false) }
  return <div className="site">
    <header className="nav-wrap">
      <a className="brand" href="#top" aria-label="AA Space Design home"><img src={A+'aa-space-design-logo.png'} alt="AA Space Design" /></a>
      <button className="menu-toggle" onClick={()=>setMenuOpen(!menuOpen)} aria-label={menuOpen?'Close menu':'Open menu'}>{menuOpen?<X/>:<Menu/>}</button>
      <nav className={menuOpen?'nav open':'nav'} aria-label="Main navigation">
        <button onClick={()=>scrollTo('top')}>Home</button><button onClick={()=>scrollTo('services')}>Services</button><button onClick={()=>scrollTo('process')}>Process</button><button onClick={()=>scrollTo('contact')}>Contact</button>
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

      <section id="projects" className="feature section-pad"><div className="feature-image"><img src={A+'1-74.webp'} alt="Completed interior project detail"/></div><div className="feature-copy"><p className="eyebrow">Built around your brief</p><h2>Spaces that look considered and work hard.</h2><p>From a new office fit-out to a focused renovation, we bring design thinking and practical coordination into one clear process.</p><div className="feature-points"><span><Check size={16}/> Space planning</span><span><Check size={16}/> Material direction</span><span><Check size={16}/> Coordinated execution</span></div><button className="button dark" onClick={()=>scrollTo('contact')}>Discuss your requirements <ArrowUpRight size={17}/></button></div></section>

      <section id="process" className="process section-pad"><div className="process-copy"><p className="eyebrow">Our approach</p><h2>From first brief to finished space.</h2><p className="lead">A simple, transparent process helps every decision stay connected to your goals, programme and site realities.</p><div className="steps">{process.map(([no,title,desc])=><div className="step" key={no}><span className="step-no">{no}</span><div><h3>{title}</h3><p>{desc}</p></div></div>)}</div></div><div className="process-image"><img src={A+'1-111.webp'} alt="Interior installation and finishing detail"/></div></section>

      <section className="quote-band section-pad"><div><p className="eyebrow">A stronger starting point</p><h2>Good spaces begin with good questions.</h2></div><button className="button light" onClick={()=>scrollTo('contact')}>Start a conversation <ArrowUpRight size={17}/></button></section>

      <section id="contact" className="contact section-pad"><div className="contact-copy"><p className="eyebrow">Let’s talk</p><h2>Tell us what your space needs next.</h2><p>Share a few details. We will use this first-draft form to shape a useful starting conversation.</p><div className="contact-note"><strong>Official contact details</strong><span>To be confirmed by Abdullah Afzal</span></div></div><form className="contact-form" onSubmit={e=>{e.preventDefault();setSubmitted(true)}}>{submitted?<div className="success"><Check size={28}/><h3>Thank you.</h3><p>Your enquiry is captured for this first draft. Official submission handling is to be connected.</p></div>:<><label>Name<input required placeholder="Your name"/></label><label>Company<input placeholder="Company name"/></label><label>Project details<textarea required rows="4" placeholder="City, space type, services or timeline"></textarea></label><button className="button dark" type="submit">Send enquiry <ArrowUpRight size={17}/></button></>}</form></section>
    </main>

    <footer className="footer"><div className="footer-brand"><img src={A+'aa-space-design-logo.png'} alt="AA Space Design"/><p>Thoughtful commercial interiors, space planning and coordinated project delivery.</p><div className="socials"><a href="#contact" aria-label="Instagram">IG</a><a href="#contact" aria-label="LinkedIn">in</a></div></div><div><h4>Explore</h4><button onClick={()=>scrollTo('services')}>Services</button><button onClick={()=>scrollTo('process')}>Process</button><button onClick={()=>scrollTo('projects')}>Projects</button></div><div><h4>Services</h4><span>Office partitioning</span><span>Commercial interiors</span><span>Turnkey projects</span></div><div><h4>Contact</h4><span>aaspacedesign.com</span><span>[Phone to confirm]</span><span>[City / address to confirm]</span></div></footer>
  </div>
}

createRoot(document.getElementById('root')).render(<App />)
