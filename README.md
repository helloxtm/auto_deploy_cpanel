# auto_deploy_cpanel

cPanel pe Git se clone karo, phir GitHub / Git Desktop se push karte hi site auto update ho jaye — `auto_deploy.php` + webhook se.

---

## Overview

1. **SSH setup** — cPanel ↔ GitHub connection
2. **cPanel Git Version Control** — repo clone
3. **Webhook + `auto_deploy.php`** — har push pe auto deploy

---

## Part 1: GitHub SSH Setup in cPanel

Pehle SSH key banana zaroori hai, warna private repo clone / pull fail hoga.

### 1. Generate SSH key

cPanel Terminal (or SSH) me:

```bash
ssh-keygen -t rsa -b 2048 -C "cpanel-username@yourdomain.com"
```

Enter 3 baar dabao (default path + empty passphrase).

### 2. Confirm defaults

File location aur passphrase dono ke liye sirf **Enter** → **Enter**.

### 3. Create SSH config

```bash
touch ~/.ssh/config
```

### 4. Permissions

```bash
chmod 600 ~/.ssh/config
```

### 5. Ownership

`cpanel-username` ko apna real username se replace karo:

```bash
chown cpanel-username:cpanel-username ~/.ssh/config
```

### 6. Authorize key in cPanel

cPanel → **SSH Access** → apni key → **Authorize**.

Public key dekhne ke liye:

```bash
cat ~/.ssh/id_rsa.pub
```

### 7. Add key to GitHub (Deploy Key)

GitHub repo → **Settings** → **Deploy Keys** → **Add deploy key**

- Title: e.g. `cPanel`
- Key: `id_rsa.pub` ka content paste karo
- Agar pull-only chahiye to write access mat do

### 8. Test connection

```bash
ssh -T git@github.com
```

Success message roughly aisa hona chahiye:

`Hi username! You've successfully authenticated...`

---

## Part 2: Clone repo with cPanel Git Version Control

1. cPanel me **Git Version Control** kholo.
2. **Create** / **Clone Repository** click karo.
3. Fill karo:
   - **Clone URL:** `git@github.com:YOUR_USER/YOUR_REPO.git` (SSH URL use karo)
   - **Repository Path:** jahan files chahiye (e.g. `public_html` ya subdomain folder)
   - **Repository Name:** koi naam
4. **Create** dabao — clone ho jayega.

Clone ke baad woh folder me `.git` hona chahiye. Deploy script isi repo pe `git fetch` / `git reset` chalata hai.

---

## Part 3: Auto deploy (`auto_deploy.php` + webhook)

### 1. File place karo

`auto_deploy.php` ko **repo root** me rakho (jahan `.git` hai), ya aise path pe jo browser se reach ho sake, e.g.:

`https://yourdomain.com/auto_deploy.php`

### 2. Secret set karo

`auto_deploy.php` me ye line edit karo:

```php
$secret = 'add your key here';
```

Strong random secret lagao (GitHub webhook secret ke saath **exact same** hona chahiye).

### 3. GitHub webhook banao

Repo → **Settings** → **Webhooks** → **Add webhook**

| Field | Value |
|--------|--------|
| **Payload URL** | `https://yourdomain.com/auto_deploy.php` |
| **Content type** | `application/json` |
| **Secret** | wahi secret jo PHP me set kiya |
| **Events** | Just the push event |
| **Active** | checked |

**Add webhook** save karo.

### 4. Script kya karta hai

Webhook hit hone pe (valid signature ke baad):

```text
git fetch origin main
git reset --hard origin/main
git clean -fd -e api/data -e api/logs
```

Matlab: latest `main` aa jati hai, local changes overwrite, aur clean (selected folders exclude).

> Branch `main` nahi hai to `auto_deploy.php` me `main` ko apni branch se change karo (e.g. `master`).

---

## Part 4: Git Desktop se deploy flow

Local pe GitHub Desktop use kar rahe ho to flow yeh hai:

1. Code change karo
2. **GitHub Desktop** → commit
3. **Push origin** (branch: `main` ya jo webhook/script use karti hai)
4. GitHub webhook fire hota hai → cPanel pe `auto_deploy.php` chalta hai
5. Server pe latest code aa jata hai — manual upload / cPanel Pull ki zaroorat nahi

Pehli baar test:

- GitHub webhook page pe **Recent Deliveries** check karo (green = OK)
- Ya browser / Postman se hit mat karo bina valid signature ke — secret ke bina `403` aayega (yeh sahi hai)

---

## Checklist

- [ ] SSH key generate + cPanel Authorize
- [ ] Deploy key GitHub pe add
- [ ] `ssh -T git@github.com` success
- [ ] cPanel **Git Version Control** se clone
- [ ] `auto_deploy.php` live URL pe + `$secret` set
- [ ] GitHub webhook URL + same secret
- [ ] Git Desktop se push → site update

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Clone / pull auth fail | Deploy key + `ssh -T` dubara check |
| Webhook `403 No signature` / `Invalid signature` | PHP `$secret` aur GitHub webhook secret match karo |
| Push pe update nahi | Branch name (`main` vs `master`), webhook Recent Deliveries, PHP `shell_exec` allowed hai ya nahi |
| Wrong folder update | `auto_deploy.php` wahi rakh o jahan `.git` hai; script repo root detect karti hai |

---

## Security notes

- Secret strong rakho, repo / docs me public mat commit karo
- Webhook URL HTTPS prefer karo
- Deploy key ideally read-only (pull only)
