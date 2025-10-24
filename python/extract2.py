import os
import re
from PyPDF2 import PdfReader
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
    """Витягти Question ID з тексту"""
    # Шаблони для пошуку Question ID
    patterns = [
        r'Question Id:\s*(\d+)',
        r'Question Is:\s*\$?(\d+)',
        r'Question Id:\s*\$?(\d+)',
        r'Question Is:\s*\$?(\d+)'
    ]
    
    for pattern in patterns:
        match = re.search(pattern, text)
        if match:
            return match.group(1)
    return None

def process_pdf(pdf_path, output_dir):
    """Обробляє 1 PDF і зберігає сторінки згруповані за Question ID"""
    print(f"📄 Обробка {pdf_path}")
    
    reader = PdfReader(pdf_path)
    total_pages = len(reader.pages)
    
    # Словник для зберігання тексту за Question ID
    question_data = {}
    
    for page_num in range(total_pages):
        page = reader.pages[page_num]
        text = page.extract_text()
        
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
            print(f"❌ Сторінка {page_num + 1}: Question ID не знайдено")
    
    # Зберегти дані за Question ID
    base_name = os.path.splitext(os.path.basename(pdf_path))[0]
    os.makedirs(output_dir, exist_ok=True)
    
    for question_id, pages in question_data.items():
        output_filename = f"txt{question_id}.txt"
        output_path = os.path.join(output_dir, output_filename)
        
        with open(output_path, "w", encoding="utf-8") as f:
            f.write(f"Question ID: {question_id}\n")
            f.write(f"Source PDF: {base_name}\n")
            f.write("=" * 50 + "\n\n")
            
            for page_data in pages:
                f.write(f"--- Сторінка {page_data['page_num']} ---\n")
                f.write(page_data['text'])
                f.write("\n\n" + "=" * 50 + "\n\n")
        
        print(f"💾 Збережено: {output_path} ({len(pages)} сторінок)")

def merge_question_files(output_dir):
    """Об'єднати файли з однаковим Question ID з різних PDF"""
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

def main():
    """Головна функція"""
    os.makedirs(TXT_DIR, exist_ok=True)
    
    # Обробити всі PDF файли
    for file in os.listdir(PDF_DIR):
        if file.lower().endswith(".pdf"):
            pdf_path = os.path.join(PDF_DIR, file)
            process_pdf(pdf_path, TXT_DIR)
    
    # Об'єднати файли з однаковим Question ID
    print("\n🔄 Об'єднання файлів з однаковим Question ID...")
    merge_question_files(TXT_DIR)
    
    print("\n✅ Обробка завершена!")

if __name__ == "__main__":
    main()
