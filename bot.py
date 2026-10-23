import logging
from telegram import Update
from telegram.ext import ApplicationBuilder, ContextTypes, MessageHandler, filters

logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)

async def handle_message(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_text = update.message.text.lower().strip()
    
    # Keu "hello" likhle "hi" reply korbe
    if user_text == "hello":
        await update.message.reply_text("hi")

if __name__ == '__main__':
    BOT_TOKEN = "8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY"

    application = ApplicationBuilder().token(BOT_TOKEN).build()

    message_handler = MessageHandler(filters.TEXT & (~filters.COMMAND), handle_message)
    application.add_handler(message_handler)

    print("Python Bot is running...")
    application.run_polling()
