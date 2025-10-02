@echo off
REM สร้างแผนผังโฟลเดอร์และไฟล์ในโฟลเดอร์ปัจจุบัน
REM บันทึกผลลัพธ์ลงในไฟล์ชื่อ 'File_Structure.txt'
tree /F /A > File_Structure.txt
echo แผนผังรายชื่อไฟล์ถูกสร้างเสร็จสมบูรณ์แล้วในชื่อ: File_Structure.txt
pause