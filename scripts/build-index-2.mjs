import { build } from 'vite'
import { createHash } from 'node:crypto'
import { copyFile, mkdtemp, readFile, readdir, rm, writeFile } from 'node:fs/promises'
import os from 'node:os'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
  const outputDir = await mkdtemp(path.join(os.tmpdir(), 'aa-homepage-build-'))
const targetAssetsDir = path.join(root, 'assets')

try {
  await build({
    root,
    configFile: path.join(root, 'vite.config.js'),
    build: { outDir: outputDir, emptyOutDir: true },
  })

  const outputAssetsDir = path.join(outputDir, 'assets')
  const outputFiles = await readdir(outputAssetsDir)
  const jsFiles = outputFiles.filter((name) => /^index\.source-.+\.js$/.test(name))
  const cssFiles = outputFiles.filter((name) => /^index-.+\.css$/.test(name))
  if (jsFiles.length !== 1 || cssFiles.length !== 1) {
    throw new Error(`Expected one app JS and CSS bundle; found ${jsFiles.length} JS and ${cssFiles.length} CSS files.`)
  }

  const jsPath = path.join(outputAssetsDir, jsFiles[0])
  const cssPath = path.join(outputAssetsDir, cssFiles[0])
  const jsBytes = await readFile(jsPath)
  const cssBytes = await readFile(cssPath)
  const cacheKey = createHash('sha256').update(jsBytes).update(cssBytes).digest('hex').slice(0, 12)
  await copyFile(jsPath, path.join(targetAssetsDir, 'index-2.js'))
  await copyFile(cssPath, path.join(targetAssetsDir, 'index-2.css'))

  for (const htmlName of ['index.html', 'projects.html']) {
    const htmlPath = path.join(root, htmlName)
    const html = await readFile(htmlPath, 'utf8')
    const nextJsTag = `src="/assets/index-2.js?v=${cacheKey}"`
    const nextCssTag = `href="/assets/index-2.css?v=${cacheKey}"`
    const updatedHtml = html
      .replace(/src="\/assets\/index-2\.js(?:\?v=[^"]*)?"/, nextJsTag)
      .replace(/href="\/assets\/index-2\.css(?:\?v=[^"]*)?"/, nextCssTag)
    if (!updatedHtml.includes(nextJsTag) || !updatedHtml.includes(nextCssTag)) {
      throw new Error(`Could not update both bundle references in ${htmlName}.`)
    }
    await writeFile(htmlPath, updatedHtml)
  }

  console.log(`Updated homepage and projects archive bundles (cache key ${cacheKey}).`)
  console.log(`JS: ${jsBytes.length} bytes; CSS: ${cssBytes.length} bytes.`)
} finally {
  await rm(outputDir, { recursive: true, force: true })
}
