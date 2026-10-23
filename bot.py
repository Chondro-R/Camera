import json
import os
import random
import string
from threading import Thread
from flask import Flask
from telegram import Update
from telegram.ext import ApplicationBuilder, CommandHandler, ContextTypes

# Flask ডামি সার্ভার (রেন্ডার যাতে সার্ভিস বন্ধ না করে)
app = Flask('')

@app.route('/')
def home():
    $text = "Bot is running!";
    return "Bot is active!"

def run_flask():
    app.run(host='0.0.0.0', port=int(os.environ.get('PORT', 8080)))

# টেলিগ্রাম বট কনফিগারেশন
BOT_TOKEN = "8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY"
BASE_WEBSITE_URL = "https://camera-app.onrender.com"  # তোর রেন্ডার ওয়েব লিংক এখানে দিবি
LINKS_FILE = 'links.json'

def load_links():
    if os.path.exists(LINKS_FILE):
        with open(LINKS_FILE, 'r') as f:
            try:
                return json.load(f)
            except:
                return {}
    return {}

def save_links(links):
    with open(LINKS_FILE, 'w') as f:
        json.dump(links, f, indent=4)

def generate_random_id(length=6):
    chars = string.ascii_letters + string.digits
    return ''.join(random.choice(chars) for _ in range(length))

async def gen_link(update: Update, context: ContextTypes.DEFAULT_TYPE):
    if not context.args:
        await update.message.reply_text("দয়া করে একটি লিংক দিন। যেমন:\n`/gen https://example.com`", parse_mode="Markdown")
        return

    target_url = context.args[0]
    unique_id = generate_random_id()
    
    links = load_links()
    links[unique_id] = target_url
    save_links(links)

    final_link = f"{BASE_WEBSITE_URL}/?id={unique_id}"

    response_text = (
        f"✅ **নতুন লিংক সফলভাবে জেনারেট হয়েছে!**\n\n"
        f"🎯 **টার্গেট সাইট:** {target_url}\n"
        f"🔗 **জেনারেটেড লিংক:**\n`{final_link}`"
    )

    await update.message.reply_text(response_text, parse_mode="Markdown")

def main():
    # ফ্লাস্ক সার্ভার আলাদা থ্রেডে চালু করা
    server_thread = Thread(target=run_flask)
    server_thread.start()

    # টেলিগ্রাম বট স্টার্ট
    application = ApplicationBuilder().token(BOT_TOKEN).build()
    application.add_handler(CommandHandler("gen", gen_link))
    
    print("Telegram Bot is running...")
    application.run_polling()

if __name__ == '__main__':
    main()
