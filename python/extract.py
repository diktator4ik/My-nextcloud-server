from pdfminer.high_level import extract_text
import re

text = extract_text("/home/diktator4ik/My-nextcloud-server/python/ophtalmology1.pdf")

# 🧹 Очистка сміття
clean_text = re.sub(r'https?://\S+|Block Time Elapsed.*|My Notebook.*|Tutorial|Full Screen|Lab Values|Notes|Calculator.*|Settings|Feedback|Suspend|End Block', '', text)
clean_text = re.sub(r'\s+', ' ', clean_text)
clean_text = re.sub(r'\x0c', '', clean_text)

# 🧩 Розбиваємо на питання
blocks = re.split(r'Question Id:\s*\d+', clean_text)
print(f"Знайдено блоків: {len(blocks)}")

for i, block in enumerate(blocks[1:6]):  # обмежимо 5 для тесту
    print(f"\n\n--- 🧠 Question {i+1} ---")

    # 🩺 Текст питання (починається з чогось типу “A 45-year-old ...”)
    q_match = re.search(r'(?:A|An|The)\s+\d{1,2}-year-old.*?(?=\s+[A-E][\.\)]\s)', block)
    question = q_match.group(0).strip() if q_match else "N/A"

    # 🔠 Варіанти
    options = re.findall(r'([A-E])[\.\)]\s(.*?)(?=\s+[A-F][\.\)]| Submit| Incorrect| Correct|$)', block)

    # ✅ Правильна відповідь
    ans_match = re.search(r'Correct answer.*?([A-E])', block)
    answer = ans_match.group(1).strip() if ans_match else "N/A"

    # 📘 Пояснення (після слова "Explanation" або просто “Cataracts…”)
    exp_match = re.search(r'(?:Explanation|Educational objective)?\s*([A-Z].{50,}?)(?=$|= Item|\Z)', block)
    explanation = exp_match.group(1).strip() if exp_match else "N/A"

    print("📝 Question:", question[:300])
    print("🔹 Options:", options)
    print("✅ Answer:", answer)
    print("📘 Explanation:", explanation[:250])

