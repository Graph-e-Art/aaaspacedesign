import assert from 'node:assert/strict'
import test from 'node:test'
import { projectImageSources, projectPreviewSlides } from './projectThumbnailData.js'

test('returns three distinct image slides and does not exceed the thumbnail limit', () => {
  const project = {
    image_url: 'https://images.example.com/cover.jpg',
    media: [
      { type: 'image', url: 'https://images.example.com/one.jpg' },
      { type: 'image', url: 'https://images.example.com/two.jpg' },
      { type: 'image', url: 'https://images.example.com/three.jpg' },
      { type: 'image', url: 'https://images.example.com/four.jpg' },
      { type: 'drive', url: 'https://drive.google.com/file/d/video-id/view', label: 'Walkthrough' },
      { type: 'drive', url: 'https://drive.google.com/file/d/second-video/view' },
    ],
  }

  const slides = projectPreviewSlides(project)
  assert.deepEqual(slides.map((slide) => slide.type), ['image', 'image', 'image'])
  assert.deepEqual(slides.map((slide) => slide.src), [
    'https://images.example.com/one.jpg',
    'https://images.example.com/two.jpg',
    'https://images.example.com/three.jpg',
  ])
})

test('fills three thumbnail positions with images followed by animated Drive previews', () => {
  const slides = projectPreviewSlides({
    image_url: 'https://images.example.com/cover.jpg',
    media: [
      { type: 'image', url: 'https://images.example.com/inside.jpg' },
      { type: 'drive', url: 'https://drive.google.com/file/d/video-one/view', label: 'Walkthrough' },
      { type: 'drive', url: 'https://drive.google.com/file/d/video-two/view', label: 'Details' },
    ],
  })

  assert.deepEqual(slides.map((slide) => slide.type), ['image', 'image', 'video'])
  assert.equal(slides[2].label, 'Walkthrough')
  assert.match(slides[2].src, /autoplay=1/)
  assert.match(slides[2].src, /mute=1/)
  assert.equal(slides[2].poster, 'https://drive.google.com/thumbnail?id=video-one&sz=w640')
})

test('adds a distinct cover image when fewer than three gallery images exist', () => {
  const slides = projectPreviewSlides({
    image_url: 'https://images.example.com/cover.jpg',
    media: [{ type: 'image', url: 'https://images.example.com/inside.jpg' }],
  })
  assert.deepEqual(slides.map((slide) => slide.src), [
    'https://images.example.com/inside.jpg',
    'https://images.example.com/cover.jpg',
  ])
})

test('does not duplicate the project cover and uses a small Drive image size', () => {
  const project = {
    image_url: 'https://drive.google.com/file/d/cover-id/view',
    media: [
      { type: 'image', url: 'https://drive.google.com/file/d/cover-id/view' },
      { type: 'image', url: 'https://drive.google.com/open?id=second-id' },
    ],
  }
  const slides = projectPreviewSlides(project)
  assert.equal(slides.length, 2)
  assert.equal(slides[0].src, 'https://drive.google.com/thumbnail?id=cover-id&sz=w640')
  assert.equal(slides[1].src, 'https://drive.google.com/thumbnail?id=second-id&sz=w640')
})

test('supports video-only projects and honors an explicit slide limit', () => {
  const project = {
    embed_url: 'https://drive.google.com/file/d/video-id/view',
    media: [
      { type: 'drive', url: 'https://drive.google.com/file/d/video-id/view' },
      { type: 'drive', url: 'https://drive.google.com/file/d/second-video/view' },
    ],
  }
  const slides = projectPreviewSlides(project)
  assert.equal(slides.length, 2)
  assert.deepEqual(slides.map((slide) => slide.type), ['video', 'video'])
  assert.equal(slides[0].poster, 'https://drive.google.com/thumbnail?id=video-id&sz=w640')
  assert.deepEqual(projectImageSources(project), [])
  assert.deepEqual(projectPreviewSlides(project, 1).map((slide) => slide.key), ['video:video-id'])
  assert.deepEqual(projectPreviewSlides(project, 0), [])
})
