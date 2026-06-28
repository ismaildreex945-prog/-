import mysql.connector
from groq import Groq

from telegram import Update, ReplyKeyboardMarkup
from telegram.ext import (
    ApplicationBuilder,
    CommandHandler,
    MessageHandler,
    ContextTypes,
    filters
)

# ==========================
# إعدادات البوت
# ==========================

TOKEN = "8346456077:AAEIxFuw27dTX9sDAhNEPy6kNrQ4YROLGPI"

client = Groq(
    api_key="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"
)

mydb = mysql.connector.connect(
  host="10c62286-ismaildreex945-76ee.h.aivencloud.com",
  port=23045, # 20168
  user="avnadmin",
  password="AVNS_87pPrZKA-h8TKh3Jg7k",
  database="defaultdb"
)

cursor = db.cursor()

logged_students = {}

# ==========================
# الذكاء الاصطناعي
# ==========================

def ask_ai(question):

    try:

        completion = client.chat.completions.create(
            model="llama-3.3-70b-versatile",
            messages=[
                {
                    "role": "system",
                    "content": """
أنت مساعد جامعي ذكي.
أجب دائماً باللغة العربية.
ساعد الطلاب في البرمجة وقواعد البيانات والشبكات
والذكاء الاصطناعي والدراسة الجامعية.
"""
                },
                {
                    "role": "user",
                    "content": question
                }
            ]
        )

        return completion.choices[0].message.content

    except Exception as e:

        return str(e)
# ==========================
# START
# ==========================

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):

    keyboard = [
        ["📄 بياناتي", "📚 موادي"],
        ["📊 نتائجي", "📝 استفسار"],
        ["🤖 اسأل الذكاء الاصطناعي"],
        ["ℹ️ مساعدة"]
    ]

    await update.message.reply_text(
        "👋 مرحباً بك في المساعد الذكي للطلاب\n\nأدخل رقم الطالب:",
        reply_markup=ReplyKeyboardMarkup(
            keyboard,
            resize_keyboard=True
        )
    )


# ==========================
# بيانات الطالب
# ==========================

async def show_info(update: Update, context: ContextTypes.DEFAULT_TYPE):

    user_id = update.message.from_user.id

    if user_id not in logged_students:
        await update.message.reply_text("❌ أدخل رقم الطالب أولاً")
        return

    cursor.execute("""
        SELECT full_name, department, level
        FROM students
        WHERE student_number=%s
    """, (logged_students[user_id],))

    student = cursor.fetchone()

    if student:

        await update.message.reply_text(
            f"👤 الاسم : {student[0]}\n"
            f"🏫 القسم : {student[1]}\n"
            f"📚 المستوى : {student[2]}"
        )

    else:

        await update.message.reply_text(
            "❌ لم يتم العثور على بيانات الطالب"
        )


# ==========================
# المواد
# ==========================

async def show_courses(update: Update, context: ContextTypes.DEFAULT_TYPE):

    user_id = update.message.from_user.id

    if user_id not in logged_students:
        await update.message.reply_text("❌ أدخل رقم الطالب أولاً")
        return

    cursor.execute("""
        SELECT c.course_name
        FROM enrollments e
        JOIN courses c
        ON e.course_code = c.course_code
        WHERE e.student_number=%s
    """, (logged_students[user_id],))

    courses = cursor.fetchall()

    if not courses:
        await update.message.reply_text("لا توجد مواد مسجلة.")
        return

    msg = "📚 المواد المسجلة:\n\n"

    for course in courses:
        msg += f"• {course[0]}\n"

    await update.message.reply_text(msg)
# ==========================
# النتائج
# ==========================

async def show_results(update: Update, context: ContextTypes.DEFAULT_TYPE):

    user_id = update.message.from_user.id

    if user_id not in logged_students:
        await update.message.reply_text("❌ أدخل رقم الطالب أولاً")
        return

    cursor.execute("""
        SELECT course_name, grade
        FROM results
        WHERE student_number=%s
    """, (logged_students[user_id],))

    results = cursor.fetchall()

    if not results:
        await update.message.reply_text("لا توجد نتائج.")
        return

    msg = "📊 النتائج:\n\n"

    for course in results:
        msg += f"{course[0]} : {course[1]}\n"

    await update.message.reply_text(msg)


# ==========================
# استقبال الرسائل
# ==========================

async def handle_message(update: Update, context: ContextTypes.DEFAULT_TYPE):

    user_id = update.message.from_user.id
    text = update.message.text

    # تسجيل الدخول برقم الطالب
    if text.isdigit():

        cursor.execute("""
            SELECT *
            FROM students
            WHERE student_number=%s
        """, (text,))

        student = cursor.fetchone()

        if student:

            logged_students[user_id] = text

            await update.message.reply_text(
                "✅ تم تسجيل الدخول بنجاح"
            )

        else:

            await update.message.reply_text(
                "❌ رقم الطالب غير صحيح"
            )

        return


    # بيانات الطالب
    if text == "📄 بياناتي":
        await show_info(update, context)
        return

    # المواد
    if text == "📚 موادي":
        await show_courses(update, context)
        return

    # النتائج
    if text == "📊 نتائجي":
        await show_results(update, context)
        return

    # الذكاء الاصطناعي
    if text == "🤖 اسأل الذكاء الاصطناعي":

        context.user_data["ai_mode"] = True

        await update.message.reply_text(
            "🤖 اكتب سؤالك."
        )

        return

    if context.user_data.get("ai_mode"):

        answer = ask_ai(text)

        await update.message.reply_text(answer)

        return
# ==========================
# الاستفسارات والمساعدة
# ==========================

    if text == "📝 استفسار":

        context.user_data["waiting_query"] = True

        await update.message.reply_text(
            "✍️ اكتب استفسارك الآن."
        )

        return


    if context.user_data.get("waiting_query"):

        if user_id not in logged_students:

            await update.message.reply_text(
                "❌ أدخل رقم الطالب أولاً."
            )

            return

        cursor.execute("""
            INSERT INTO queries
            (student_number, question)
            VALUES (%s,%s)
        """, (
            logged_students[user_id],
            text
        ))

        db.commit()

        context.user_data["waiting_query"] = False

        await update.message.reply_text(
            "✅ تم حفظ استفسارك."
        )

        return


    if text == "ℹ️ مساعدة":

        await update.message.reply_text(
            """
📄 بياناتي : عرض بيانات الطالب

📚 موادي : عرض المواد المسجلة

📊 نتائجي : عرض النتائج

📝 استفسار : إرسال استفسار للإدارة

🤖 اسأل الذكاء الاصطناعي : سؤال الذكاء الاصطناعي
"""
        )

        return

# ==========================
# تشغيل البوت
# ==========================

app = ApplicationBuilder().token(TOKEN).build()

app.add_handler(
    CommandHandler(
        "start",
        start
    )
)

app.add_handler(
    MessageHandler(
        filters.TEXT & ~filters.COMMAND,
        handle_message
    )
)

print("Bot Running...")

app.run_polling()
