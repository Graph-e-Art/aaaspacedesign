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

The approved client page is now `index.html`. The previous homepage is preserved as `index-3.html`. The deployment hook copies `dist/.` (including `projects.html`), the `index-3.html` backup, and root `assets/` into the selected document root, so cPanel does not need Node.js or npm for deployment. The root `index.html` and `assets/` fallback also allow the site to work when cPanel points the domain directly at the Git checkout.

When the shared React source changes, run `pnpm build`. It refreshes the homepage and `projects.html` archive bundle references, then writes both pages and their assets into `dist/` without replacing the source pages. Commit the generated outputs, push them, then deploy that commit from cPanel.

## If domain still shows blank page

Check these items in order:

- Domain document root matches `DEPLOYPATH`.
- `public_html/index.html` exists after deployment.
- `public_html/assets/index-*.js` exists.
- Browser DevTools Console has no 404 errors for `/assets/...`.
- cPanel deployment log shows the `dist/` copy task completed.

## Enquiry form API and admin panel

Upload these folders into the public website document root:

```text
api/
admin/
config/
```

Keep the real credentials outside the public document root:

```text
/home/aaspaced/cred/.env
```

The PHP API reads that file and writes enquiries to the existing `contact_enquiries` MySQL table. The frontend submits to:

```text
/api/submit-enquiry.php
```

Open the protected admin dashboard at:

```text
/admin/
```

Use `ADMIN_USERNAME` and `ADMIN_PASSWORD` from the private `.env` file. The initial agreed credentials are `admin` and `1234`; change them after first live deployment.

The API uses cPanel's PHP `mail()` transport to notify `MAIL_TO`. The admin dashboard provides status updates, internal notes, and a manual WhatsApp link. Meta WhatsApp API credentials are not required.

## Dynamic project archive

The project archive uses the existing MySQL connection from `/home/aaspaced/cred/.env`:

```text
DB_HOST=...
DB_NAME=...
DB_USER=...
DB_PASS=...
```

For a fresh installation, create the project tables from `database/schema.sql`. For an existing installation, keep using the existing MySQL tables and run the migrations below.

Project content does **not** create Git commits. This is intentional: project records belong in MySQL, while GitHub should track code, schema, and deployment changes. cPanel Git deployment copies `prj-deploy.php` with the public site files.

## Dashboard tabs and project media

`/admin/?tab=messages` opens **User Messages**. ` /admin/?tab=projects` opens **Projects Submit**.

Project form includes an optional **Thumbnail / cover image URL**. Upload the image in cPanel File Manager under `public_html/assets/project-thumbnails/`, then paste its full public URL. Add gallery media in the media rows; select `Image` or `Google Drive Video`, paste the URL, then click **+ Add image or Google Drive video** as needed. The database stores Drive video rows as `media_type='drive'` in `project_media`.

The Projects Submit dashboard lists every project entry, not only featured projects. Each row shows a cover thumbnail when available; projects without an image show a placeholder. The homepage Portfolio displays the five projects assigned to Portfolio 1–5 plus a sixth **See More Projects** tile linking to `/projects.html`, the full published archive with date filters and pagination.

## Client tags and client project galleries

Back up MySQL before deployment. For an existing site, run `database/migrations/20261023_client_tags.sql` once in phpMyAdmin. It creates client tags, seeds the approved roster, and adds nullable `projects.client_id`; existing project rows stay unassigned until edited. Do not run the migration twice because its `ALTER TABLE` step is one-time.

The protected `/admin/?tab=projects` page lets an admin add client tags and choose one when entering each new project. The homepage client marquee loads names from `/api/clients.php`; selecting a client opens published projects, and selecting a project opens its existing media gallery. Fresh installations can use the updated `database/schema.sql` instead of the migration.

## Featured portfolio slots

Back up MySQL first. For an existing site, run `database/migrations/20261024_featured_projects.sql` once in phpMyAdmin after the projects, `project_media`, and client-tag migrations. It adds nullable `projects.featured_order`, enforces one project per slot, and assigns the five approved project records to Portfolio 1–5. Other entries stay unfeatured and remain visible in the dashboard and full archive. Do not run this `ALTER TABLE` migration twice.

Apply the migration before deploying the code that reads `featured_order`. For fresh installs, the updated `database/schema.sql` includes the slot column and unique index; assign the five project records from the dashboard after entering them.
