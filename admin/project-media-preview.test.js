import assert from 'node:assert/strict'
import test from 'node:test'
import { getDriveFileId, getDriveThumbnailUrl, getDriveVideoPreviewUrl } from './project-media-preview.js'

test('reads a file ID from a standard Google Drive share URL', () => {
  assert.equal(getDriveFileId('https://drive.google.com/file/d/abc123/view?usp=sharing'), 'abc123')
})

test('reads a file ID from an open?id Drive URL', () => {
  assert.equal(getDriveFileId('https://drive.google.com/open?id=abc123'), 'abc123')
})

test('does not treat non-Drive URLs as Drive files', () => {
  assert.equal(getDriveFileId('https://example.com/file/d/abc123/view'), '')
})

test('builds a Drive thumbnail URL for image previews', () => {
  assert.equal(
    getDriveThumbnailUrl('https://drive.google.com/file/d/abc123/view'),
    'https://drive.google.com/thumbnail?id=abc123&sz=w640',
  )
})

test('builds a muted autoplay Drive player URL for video previews', () => {
  assert.equal(
    getDriveVideoPreviewUrl('https://drive.google.com/file/d/abc123/view?usp=sharing'),
    'https://drive.google.com/file/d/abc123/preview?autoplay=1&mute=1',
  )
})

test('rejects non-Drive URLs as video embeds', () => {
  assert.equal(getDriveVideoPreviewUrl('https://example.com/video.mp4'), '')
})
