# Deploy บน Vercel (HTTPS — มือถือเล่นได้)

โปรเจกต์นี้รองรับ **Vercel** แล้ว — ได้ **HTTPS ฟรี** กล้องมือถือใช้ได้

## ขั้นตอน Deploy

### 1. อัปโหลดโค้ดขึ้น GitHub
```bash
cd c:\xampp\htdocs\rt-gamear1
git init
git add .
git commit -m "AR Lucky Elephant Hunt"
git remote add origin https://github.com/YOUR_USER/ar-elephant-hunt.git
git push -u origin main
```

### 2. เชื่อม Vercel
1. เปิด [Vercel Dashboard](https://vercel.com/phumcj11s-projects)
2. **Add New → Project**
3. Import repo จาก GitHub
4. กด **Deploy**

### 3. ตั้งค่า Environment (แนะนำ)
ใน Vercel → Project → Settings → Environment Variables:

| Name | Value |
|------|-------|
| `SESSION_SECRET` | รหัสลับยาว 32 ตัวขึ้นไป |
| `POSTGRES_URL` | (ถ้าใช้ Vercel Postgres สำหรับเก็บคูปอง) |

ถ้าไม่ใส่ `POSTGRES_URL` เกมยังเล่นได้ แต่**ไม่บันทึก**ลง database

### 4. เปิดเล่น
หลัง deploy จะได้ URL แบบ:
```
https://your-project.vercel.app
```

| หน้า | URL |
|------|-----|
| หน้าแรก | `/` |
| **โหมดง่าย (แนะนำ)** | `/demo.html` |
| เกม AR | `/game.html` |
| รับรางวัล | `/reward.html` |

---

## วิธีเล่นบนมือถือ (หลัง deploy Vercel)

1. เปิด `https://xxx.vercel.app/demo.html` → แตะช้าง 3 ครั้ง → รับรางวัล  
   **(ไม่ต้องใช้กล้อง — ง่ายที่สุด)**

2. หรือเปิด `https://xxx.vercel.app/game.html` → Allow กล้อง → สแกนการ์ด MindAR

---

## XAMPP vs Vercel

| | XAMPP (localhost) | Vercel |
|--|-------------------|--------|
| มือถือผ่าน IP | ❌ กล้องไม่ได้ | ✅ HTTPS |
| PHP Admin | ✅ | ❌ (ใช้ XAMPP สำหรับ admin) |
| โหมดง่าย | `/demo.php` | `/demo.html` |

---

## หมายเหตุ
- ไฟล์ `.php` ใช้กับ **XAMPP** เท่านั้น
- ไฟล์ `.html` + `/api/*.js` ใช้กับ **Vercel**
- Admin panel (`/admin/`) ยังใช้บน XAMPP ได้
