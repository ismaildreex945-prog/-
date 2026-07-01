import threading
from http.server import BaseHTTPRequestHandler, HTTPServer


class WebServerHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        self.send_response(200)
        self.send_header('Content-type', 'text/html; charset=utf-8')
        self.end_headers()
        self.wfile.write("البوت يعمل بنجاح وبشكل مستمر!".encode('utf-8'))

def run_web_server():
    try:
        server = HTTPServer(('0.0.0.0', 10000), WebServerHandler)
        server.serve_forever()
    except Exception as e:
        pass

threading.Thread(target=run_web_server, daemon=True).start()


import psycopg2
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

db = psycopg2.connect(
  host="aws-0-eu-west-1.pooler.supabase.com",
  port=5432,
  user="postgres.rpornaknimwhcayzbodb",
  password="ismailidris11",
  database="postgres"
)

cursor = db.cursor()

logged_students = {}

# ==========================
# الذكاء الاصطناعي
# ==========================

SYSTEM_PROMPT = """
أنت مساعد جامعي ذكي ومتخصص تابع لجامعة الرباط الوطني.
اسمك "المساعد الذكي".

مهمتك الأساسية:
- مساعدة الطلاب الجامعيين في كل ما يتعلق بدراستهم
- الإجابة على الأسئلة الأكاديمية بشكل شامل ومفصّل
- شرح المفاهيم بأمثلة عملية واضحة
- تقديم نصائح للمذاكرة وإدارة الوقت

المواد التي تتخصص فيها:
• البرمجة (Python, Java, C++, PHP, JavaScript)
• قواعد البيانات (SQL, MySQL, MongoDB)
• الشبكات وأمن المعلومات
• الذكاء الاصطناعي وتعلم الآلة
• هندسة البرمجيات وأنماط التصميم
• الرياضيات والإحصاء
• أي مادة جامعية أخرى

قواعد الإجابة:
1. أجب دائماً باللغة العربية إلا لو الطالب سألك بلغة أخرى
2. إجاباتك شاملة ومفصّلة وليست مختصرة اختصاراً مخلاً
3. استخدم الأمثلة العملية والكود عند الحاجة
4. لو الطالب سألك سؤالاً برمجياً، اشرح الكود سطراً سطراً
5. لو السؤال غامض، اطرح سؤالاً توضيحياً قبل الإجابة
6. شجّع الطالب وكن إيجابياً في ردودك
7. لو الموضوع خارج نطاق الدراسة الجامعية، وجّه الطالب بلطف
8. استخدم الرموز والتنسيق لجعل الإجابة واضحة (✅ ❌ 📌 💡 🔹)

تذكّر: أنت تتحدث مع طالب جامعي يحتاج مساعدة حقيقية، لا إجابات مبتسرة.
"""

def get_user_history(user_id):
    if user_id not in conversation_history:
        conversation_history[user_id] = []
    return conversation_history[user_id]

def add_to_history(user_id, role, content):
    history = get_user_history(user_id)
    history.append({"role": role, "content": content})
    # احتفظ بآخر 10 رسائل فقط عشان ما تتجاوز الـ context
    if len(history) > 10:
        conversation_history[user_id] = history[-10:]

def clear_history(user_id):
    conversation_history[user_id] = []

# ==========================
# كشف أسئلة الطالب عن بياناته
# ==========================

KEYWORDS_INFO    = ["بياناتي", "معلوماتي", "اسمي", "قسمي", "مستواي"]
KEYWORDS_COURSES = ["موادي", "مواد", "مسجل", "المواد المسجلة", "كورساتي", "مقرراتي"]
KEYWORDS_RESULTS = ["نتيجتي", "نتائجي", "درجاتي", "درجتي", "نتيجة", "نجحت", "رسبت", "علاماتي"]

def check_student_query(text, telegram_user_id):
    if telegram_user_id not in logged_students:
        return None

    student_number = logged_students[telegram_user_id]

    is_info    = any(kw in text for kw in KEYWORDS_INFO)
    is_courses = any(kw in text for kw in KEYWORDS_COURSES)
    is_results = any(kw in text for kw in KEYWORDS_RESULTS)

    if not (is_info or is_courses or is_results):
        return None

    response_parts = []

    if is_info:
        try:
            cursor.execute(
                "SELECT full_name, department, level FROM students WHERE student_number=%s",
                (student_number,)
            )
            student = cursor.fetchone()
            if student:
                response_parts.append(
                    "📄 بياناتك الشخصية:\n"
                    f"👤 الاسم: {student[0]}\n"
                    f"🏫 القسم: {student[1]}\n"
                    f"📚 المستوى: {student[2]}"
                )
            else:
                response_parts.append("❌ لم يتم العثور على بياناتك.")
        except Exception as e:
            response_parts.append(f"⚠️ خطأ في جلب البيانات: {e}")

    if is_courses:
        try:
            cursor.execute("""
                SELECT c.course_name
                FROM enrollments e
                JOIN courses c ON e.course_code = c.course_code
                WHERE e.student_number=%s
            """, (student_number,))
            courses = cursor.fetchall()
            if courses:
                courses_text = "\n".join([f"🔹 {c[0]}" for c in courses])
                response_parts.append(f"📚 المواد المسجلة:\n{courses_text}")
            else:
                response_parts.append("📚 لا توجد مواد مسجلة حالياً.")
        except Exception as e:
            response_parts.append(f"⚠️ خطأ في جلب المواد: {e}")

    if is_results:
        try:
            cursor.execute(
                "SELECT course_name, grade FROM results WHERE student_number=%s",
                (student_number,)
            )
            results = cursor.fetchall()
            if results:
                lines = []
                gpa_points = []
                total_scores = []
                for r in results:
                    info = score_to_grade_info(r[1])
                    line = f"📘 {r[0]}: "
                    if info["score"] is not None:
                        line += f"{info['score']:.0f}/100  "
                    line += f"{info['letter']} ({info['arabic']})"
                    lines.append(line)
                    if info["gpa"] is not None:
                        gpa_points.append(info["gpa"])
                    if info["score"] is not None:
                        total_scores.append(info["score"])
                results_text = "\n".join(lines)
                msg = f"📊 نتائجك:\n{results_text}\n\n━━━━━━━━━━━━━━━━"
                if total_scores:
                    msg += f"\n📊 متوسط الدرجات: {sum(total_scores)/len(total_scores):.1f} / 100"
                if gpa_points:
                    gpa = sum(gpa_points) / len(gpa_points)
                    msg += f"\n🎓 المعدل التراكمي: {gpa:.2f} / 4.00"
                    msg += f"\n📈 التقدير: {gpa_label(gpa)}"
                response_parts.append(msg)
            else:
                response_parts.append("📊 لا توجد نتائج مسجلة حالياً.")
        except Exception as e:
            response_parts.append(f"⚠️ خطأ في جلب النتائج: {e}")

    return "\n\n".join(response_parts)

def ask_ai(question, user_id=None):
    try:
        history = get_user_history(user_id) if user_id else []

        # أضف سؤال المستخدم للتاريخ
        if user_id:
            add_to_history(user_id, "user", question)

        messages = [{"role": "system", "content": SYSTEM_PROMPT}] + (
            get_user_history(user_id) if user_id else [{"role": "user", "content": question}]
        )

        completion = client.chat.completions.create(
            model="llama-3.3-70b-versatile",
            messages=messages,
            max_tokens=2048,
            temperature=0.7,
        )

        answer = completion.choices[0].message.content

        # أضف رد المساعد للتاريخ
        if user_id:
            add_to_history(user_id, "assistant", answer)

        return answer

    except Exception as e:
        return f"⚠️ حدث خطأ: {str(e)}"
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
# حساب المعدل التراكمي
# ==========================

def score_to_grade_info(grade_val):
    """
    يقبل درجة رقمية من 100 أو حرف (A/B/...)
    يرجع dict فيه: score, letter, arabic, gpa_points
    """
    grade_str = str(grade_val).strip()

    # حاول تحوّله لرقم أولاً
    try:
        score = float(grade_str)
    except ValueError:
        score = None

    if score is not None:
        # درجة رقمية من 100
        if score >= 90:
            letter, arabic, gpa = "A",  "امتياز",      4.0
        elif score >= 85:
            letter, arabic, gpa = "B+", "جيد جداً+",   3.5
        elif score >= 80:
            letter, arabic, gpa = "B",  "جيد جداً",    3.0
        elif score >= 75:
            letter, arabic, gpa = "C+", "جيد+",        2.5
        elif score >= 70:
            letter, arabic, gpa = "C",  "جيد",         2.0
        elif score >= 60:
            letter, arabic, gpa = "D",  "مقبول",       1.0
        else:
            letter, arabic, gpa = "F",  "راسب",        0.0
    else:
        # درجة حرفية
        score = None
        mapping = {
            "A+": ("A+", "امتياز+",    4.0),
            "A":  ("A",  "امتياز",     4.0),
            "A-": ("A-", "امتياز-",    3.7),
            "B+": ("B+", "جيد جداً+", 3.3),
            "B":  ("B",  "جيد جداً",  3.0),
            "B-": ("B-", "جيد جداً-", 2.7),
            "C+": ("C+", "جيد+",      2.3),
            "C":  ("C",  "جيد",       2.0),
            "C-": ("C-", "جيد-",      1.7),
            "D+": ("D+", "مقبول+",    1.3),
            "D":  ("D",  "مقبول",     1.0),
            "F":  ("F",  "راسب",      0.0),
        }
        info = mapping.get(grade_str.upper(), None)
        if info:
            letter, arabic, gpa = info
        else:
            letter, arabic, gpa = grade_str, grade_str, None

    return {"score": score, "letter": letter, "arabic": arabic, "gpa": gpa}

def gpa_label(gpa):
    if gpa >= 3.7:   return "ممتاز 🌟"
    elif gpa >= 3.0: return "جيد جداً ✅"
    elif gpa >= 2.0: return "جيد 👍"
    elif gpa >= 1.0: return "مقبول ⚠️"
    else:            return "ضعيف ❌"

# للتوافق مع الكود القديم
def grade_to_arabic(grade):
    return score_to_grade_info(grade)["arabic"]

def grade_to_gpa(grade):
    return score_to_grade_info(grade)["gpa"]

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
        await update.message.reply_text("لا توجد نتائج مسجلة بعد.")
        return

    msg = "📊 *نتائجك الدراسية:*\n"
    msg += "━━━━━━━━━━━━━━━━\n\n"

    gpa_points = []
    total_scores = []

    for course in results:
        course_name = course[0]
        grade_val   = course[1]
        info = score_to_grade_info(grade_val)

        msg += f"📘 {course_name}\n"

        if info["score"] is not None:
            msg += f"   الدرجة: {info['score']:.0f} / 100\n"

        msg += f"   التقدير: {info['letter']}  ({info['arabic']})\n"

        if info["gpa"] is not None:
            msg += f"   النقاط: {info['gpa']:.1f} / 4.0\n"
            gpa_points.append(info["gpa"])

        if info["score"] is not None:
            total_scores.append(info["score"])

        msg += "\n"

    msg += "━━━━━━━━━━━━━━━━\n"

    if total_scores:
        avg_score = sum(total_scores) / len(total_scores)
        msg += f"📊 متوسط الدرجات: {avg_score:.1f} / 100\n"

    if gpa_points:
        gpa = sum(gpa_points) / len(gpa_points)
        msg += f"🎓 *المعدل التراكمي (GPA):* {gpa:.2f} / 4.00\n"
        msg += f"📈 التقدير العام: {gpa_label(gpa)}\n"

    msg += f"📚 عدد المواد: {len(results)}"

    await update.message.reply_text(msg, parse_mode="Markdown")


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


    # أي زرار من الأزرار الرئيسية يوقف وضع AI والاستفسار
    main_buttons = ["📄 بياناتي", "📚 موادي", "📊 نتائجي",
                    "📝 استفسار", "ℹ️ مساعدة", "🤖 اسأل الذكاء الاصطناعي"]
    if text in main_buttons:
        context.user_data["ai_mode"] = False
        context.user_data["waiting_query"] = False

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
        clear_history(user_id)

        await update.message.reply_text(
            "🤖 مرحباً! أنا المساعد الذكي.\n\n"
            "اكتب سؤالك وسأجيبك بشكل شامل ومفصّل.\n"
            "يمكنك الاستمرار في المحادثة وسأتذكر السياق.\n\n"
            "اكتب 🔄 إعادة تشغيل لبدء محادثة جديدة."
        )

        return

    if text == "🔄 إعادة تشغيل" and context.user_data.get("ai_mode"):
        clear_history(user_id)
        await update.message.reply_text("🔄 تم مسح المحادثة، ابدأ سؤالك من جديد.")
        return

    if context.user_data.get("ai_mode"):

        await update.message.reply_text("⏳ جاري التفكير...")

        try:
            # أولاً تحقق لو الطالب سأل عن بياناته من قاعدة البيانات
            db_answer = check_student_query(text, user_id)

            if db_answer:
                await update.message.reply_text(db_answer)
            else:
                answer = ask_ai(text, user_id=user_id)
                await update.message.reply_text(answer)

        except Exception as e:
            print(f"[AI ERROR] {e}")
            await update.message.reply_text(
                f"⚠️ حدث خطأ أثناء معالجة سؤالك:\n{str(e)}"
            )

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

async def reset_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.message.from_user.id
    clear_history(user_id)
    context.user_data["ai_mode"] = False
    await update.message.reply_text("🔄 تم إعادة تشغيل المحادثة.")

app.add_handler(CommandHandler("reset", reset_command))

app.add_handler(
    MessageHandler(
        filters.TEXT & ~filters.COMMAND,
        handle_message
    )
)

print("Bot Running...")

app.run_polling()
