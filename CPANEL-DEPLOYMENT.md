# cPanel Deployment

## Why blank page happened

The repository contains a Vite React source project. cPanel was serving the source `index.html`, which imports `/src/main.jsx`. Browsers cannot execute this JSX source directly. cPanel must run the production build and serve the generated `dist` files.

## Recommended setup

1. In cPanel Git Version Control, open the repository deployment settings.
2. Confirm deployment branch is `main-g247`.
3. Keep the repository's `.cpanel.yml` enabled.
4. Check `DEPLOYPATH` in `.cpanel.yml`:
   - Main domain: `$HOME/public_html/`
   - Addon domain: replace it with the addon's actual document root, for example `$HOME/example.com/`.
5. Run **Update from Remote** / **Pull or Fetch**.
6. Run **Deploy HEAD Commit**.
7. Confirm the deployment log contains successful `npm install`, `npm run build`, and copy steps.
8. Clear browser cache and test the domain in a private window.

The repository includes the tested production build in `dist/`, `index.html`, and root `assets/`. The deployment hook copies `dist/.` into the selected document root, so cPanel does not need Node.js or npm for this deployment. The root `index.html` and `assets/` fallback also allow the site to work when cPanel points the domain directly at the Git checkout.

When source code changes later, run `pnpm build` locally, commit the updated `dist/`, root `index.html`, and root `assets/`, push them, then deploy that commit from cPanel.

## If domain still shows blank page

Check these items in order:

- Domain document root matches `DEPLOYPATH`.
- `public_html/index.html` exists after deployment.
- `public_html/assets/index-*.js` exists.
- Browser DevTools Console has no 404 errors for `/assets/...`.
- cPanel deployment log shows the `dist/` copy task completed.
