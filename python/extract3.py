import os
import re
import pdfplumber
import glob

PDF_DIR = "./pdf"
TXT_DIR = "./txt"

def clean_text(text):
    """Очистка тексту від сміття"""
    text = re.sub(r'https?://\S+|Block Time Elapsed.*|My Notebook.*|Tutorial|Full Screen|Lab Values|Notes|Calculator.*|Settings|Feedback|Suspend|End Block', '', text)
    text = re.sub(r'\s+', ' ', text)
    text = re.sub(r'\x0c', '', text)
    return text.strip()

def extract_question_id(text):
    """Витягти Question ID з тексту - розширені шаблони"""
    # Розширені шаблони для пошуку Question ID
    patterns = [
        r'Question\s+Id:\s*[§\$]?\s*(\d+)',
        r'Question\s+Is:\s*[§\$]?\s*(\d+)',
        r'Question\s*Id:\s*[§\$]?\s*(\d+)',
        r'Question\s*Is:\s*[§\$]?\s*(\d+)',
        r'Question\s+list?:\s*[§\$]?\s*(\d+)',
        r'Question\s+Id:\s*(\d{3,})',
        r'Question\s+Is:\s*(\d{3,})',
        r'Item\s+\d+\s+of\s+\d+\s+Question\s+Id:\s*(\d+)',
        r'#\s*Item\s+\d+\s+of\s+\d+.*?Question.*?:\s*(\d+)'
    ]
    
    for pattern in patterns:
        matches = re.findall(pattern, text, re.IGNORECASE | re.MULTILINE)
        if matches:
            return matches[0]
    
    # Додатковий пошук у рядках, що містять "Question"
    lines = text.split('\n')
    for line in lines:
        if 'question' in line.lower():
            # Шукаємо числа в рядках з "Question"
            numbers = re.findall(r'\b(\d{3,})\b', line)
            if numbers:
                return numbers[0]
    
    return None

def process_pdf(pdf_path, output_dir):
    """Обробляє 1 PDF і зберігає сторінки згруповані за Question ID"""
    print(f"📄 Обробка {pdf_path}")
    
    try:
        with pdfplumber.open(pdf_path) as pdf:
            total_pages = len(pdf.pages)
            
            # Словник для зберігання тексту за Question ID
            question_data = {}
            
            for page_num in range(total_pages):
                page = pdf.pages[page_num]
                
                # Використовуємо розширене видобування тексту
                text = page.extract_text(
                    x_tolerance=1,
                    y_tolerance=1,
                    keep_blank_chars=False,
                    use_text_flow=True
                )
                
                if not text:
                    print(f"⚠️  Сторінка {page_num + 1}: текст не знайдено")
                    continue
                
                # Очистити текст
                cleaned_text = clean_text(text)
                
                # Знайти Question ID
                question_id = extract_question_id(text)
                
                if question_id:
                    if question_id not in question_data:
                        question_data[question_id] = []
                    
                    question_data[question_id].append({
                        'page_num': page_num + 1,
                        'text': cleaned_text
                    })
                    print(f"✅ Сторінка {page_num + 1}: Question ID {question_id}")
                else:
                    # Зберегти сторінки без Question ID для аналізу
                    unknown_id = f"unknown_{page_num + 1}"
                    if unknown_id not in question_data:
                        question_data[unknown_id] = []
                    
                    question_data[unknown_id].append({
                        'page_num': page_num + 1,
                        'text': cleaned_text
                    })
                    print(f"❌ Сторінка {page_num + 1}: Question ID не знайдено")
                    
                    # Зберегти оригінальний текст для дебагу
                    debug_path = os.path.join(output_dir, f"debug_page_{page_num + 1}.txt")
                    with open(debug_path, "w", encoding="utf-8") as f:
                        f.write(f"Оригінальний текст сторінки {page_num + 1}:\n")
                        f.write("=" * 50 + "\n")
                        f.write(text)
                        f.write(f"\n\nОчищений текст:\n")
                        f.write("=" * 50 + "\n")
                        f.write(cleaned_text)
            
            # Зберегти дані за Question ID
            base_name = os.path.splitext(os.path.basename(pdf_path))[0]
            os.makedirs(output_dir, exist_ok=True)
            
            for question_id, pages in question_data.items():
                if question_id.startswith('unknown_'):
                    output_filename = f"{question_id}.txt"
                else:
                    output_filename = f"txt{question_id}.txt"
                
                output_path = os.path.join(output_dir, output_filename)
                
                with open(output_path, "w", encoding="utf-8") as f:
                    if question_id.startswith('unknown_'):
                        f.write(f"UNKNOWN Question ID - Сторінка {pages[0]['page_num']}\n")
                    else:
                        f.write(f"Question ID: {question_id}\n")
                    f.write(f"Source PDF: {base_name}\n")
                    f.write("=" * 50 + "\n\n")
                    
                    for page_data in pages:
                        f.write(f"--- Сторінка {page_data['page_num']} ---\n")
                        f.write(page_data['text'])
                        f.write("\n\n" + "=" * 50 + "\n\n")
                
                print(f"💾 Збережено: {output_path} ({len(pages)} сторінок)")
                
    except Exception as e:
        print(f"❌ Помилка при обробці {pdf_path}: {e}")

def merge_question_files(output_dir):
    """Об'єднати файли з однаковим Question ID з різних PDF"""
    print("\n🔄 Об'єднання файлів з однаковим Question ID...")
    
    # Знайти всі файли txt{number}.txt
    pattern = os.path.join(output_dir, "txt*.txt")
    txt_files = glob.glob(pattern)
    
    # Групувати файли за Question ID
    question_files = {}
    for file_path in txt_files:
        filename = os.path.basename(file_path)
        # Витягти number з txt{number}.txt
        match = re.match(r'txt(\d+)\.txt', filename)
        if match:
            question_id = match.group(1)
            if question_id not in question_files:
                question_files[question_id] = []
            question_files[question_id].append(file_path)
    
    # Об'єднати файли з однаковим Question ID
    for question_id, files in question_files.items():
        if len(files) > 1:
            merged_filename = f"txt{question_id}_merged.txt"
            merged_path = os.path.join(output_dir, merged_filename)
            
            with open(merged_path, "w", encoding="utf-8") as outfile:
                outfile.write(f"MERGED FILES - Question ID: {question_id}\n")
                outfile.write("=" * 60 + "\n\n")
                
                for file_path in files:
                    with open(file_path, "r", encoding="utf-8") as infile:
                        outfile.write(f"--- From: {os.path.basename(file_path)} ---\n")
                        outfile.write(infile.read())
                        outfile.write("\n" + "=" * 60 + "\n\n")
            
            print(f"🔄 Об'єднано {len(files)} файлів для Question ID {question_id}: {merged_path}")

def analyze_pdf_structure(pdf_path):
    """Аналізує структуру PDF для дебагу"""
    print(f"\n🔍 Аналіз структури PDF: {pdf_path}")
    with pdfplumber.open(pdf_path) as pdf:
        for page_num in range(min(3, len(pdf.pages))):  # Перші 3 сторінки
            page = pdf.pages[page_num]
            text = page.extract_text()
            print(f"\n--- Сторінка {page_num + 1} ---")
            if text:
                lines = text.split('\n')
                for i, line in enumerate(lines[:10]):  # Перші 10 рядків
                    print(f"{i+1}: {line}")
            else:
                print("Текст не знайдено")

def main():
    """Головна функція"""
    os.makedirs(TXT_DIR, exist_ok=True)
    
    # Спочатку проаналізуємо структуру першого PDF
    pdf_files = [f for f in os.listdir(PDF_DIR) if f.lower().endswith(".pdf")]
    if pdf_files:
        analyze_pdf_structure(os.path.join(PDF_DIR, pdf_files[0]))
    
    # Обробити всі PDF файли
    for file in pdf_files:
        pdf_path = os.path.join(PDF_DIR, file)
        process_pdf(pdf_path, TXT_DIR)
    
    # Об'єднати файли з однаковим Question ID
    merge_question_files(TXT_DIR)
    
    print("\n✅ Обробка завершена!")
    print(f"\n📊 Результати збережено в: {TXT_DIR}")

if __name__ == "__main__":
    main()
