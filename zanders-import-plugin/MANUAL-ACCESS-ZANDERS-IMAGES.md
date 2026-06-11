# How to Access Zanders Firearm Images Manually

Use an FTP client on your computer to connect to Zanders and download images by item number.

## 1. FTP connection

| Setting   | Value              |
|----------|--------------------|
| **Host** | `ftp2.gzanders.com` |
| **Port** | 21 (FTP) or 990 (FTPS if they require it) |
| **Username** | Your Zanders FTP username |
| **Password** | Your Zanders FTP password |

Use **FileZilla** (or any FTP client): File → Site Manager → New Site, then enter the above.

## 2. Where the images are

After you connect, go to one of these folders on the server:

- **`Inventory/Images_2/`** – JPGs (primary)
- **`Inventory/Images/`** – GIFs / other

So the full path on the server is something like:

- `Inventory/Images_2/12345.jpg`
- `Inventory/Images/12345.gif`

## 3. Filename = item number

Image filenames match the Zanders **Item#** (SKU):

- `Item#.jpg` in `Inventory/Images_2/` or `Inventory/Images/`
- Examples: `12345.jpg`, `67890.gif`

If you have a product SKU, look for that exact filename (with `.jpg` first, then `.gif` if needed) in those folders.

## 4. Download

- In your FTP client, open `Inventory/Images_2` (and/or `Inventory/Images`).
- Find the file (e.g. `12345.jpg`) and download it to your computer.
- You can then upload that file into WordPress (Media Library or product image) as needed.

## If you don’t have FTP credentials

Get **FTP host, username, and password** from Zanders (your rep or their dealer support). The host is always `ftp2.gzanders.com`; they supply the login.
