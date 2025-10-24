import os
import re
from pdfminer.high_level import extract_text

PDF_DIR = "./pdf"
TXT_DIR = "./txt"
CHUNK_SIZE = 3000  # символів у кожному фрагменті

def clean_text(text):
    """Очистка тексту від сміття"""
    text = re.sub(r'https?://\S+|Block Time Elapsed.*|My Notebook.*|Tutorial|Full Screen|Lab Values|Notes|Calculator.*|Settings|Feedback|Suspend|End Block', '', text)
    text = re.sub(r'\s+', ' ', text)
    text = re.sub(r'\x0c', '', text)
    return text.strip()

def split_text(text, chunk_size=CHUNK_SIZE):
    """Розбиває текст на шматки з логічним контекстом"""
    sentences = re.split(r'(?<=[.!?]) +', text)
    chunks, current = [], ""
    for sent in sentences:
        if len(current) + len(sent) < chunk_size:
            current += sent + " "
        else:
            chunks.append(current.strip())
            current = sent + " "
    if current:
        chunks.append(current.strip())
    return chunks

def process_pdf(pdf_path, output_dir):
    """Обробляє 1 PDF і створює TXT-файли"""
    print(f"📄 Обробка {pdf_path}")
    text = extract_text(pdf_path)
    text = clean_text(text)
    chunks = split_text(text)

    base = os.path.splitext(os.path.basename(pdf_path))[0]
    os.makedirs(output_dir, exist_ok=True)

    for i, chunk in enumerate(chunks):
        out_file = os.path.join(output_dir, f"{base}_part_{i+1}.txt")
        with open(out_file, "w", encoding="utf-8") as f:
            f.write(chunk)
        print(f"✅ Збережено: {out_file}")

def main():
    os.makedirs(TXT_DIR, exist_ok=True)
    for file in os.listdir(PDF_DIR):
        if file.lower().endswith(".pdf"):
            pdf_path = os.path.join(PDF_DIR, file)
            process_pdf(pdf_path, TXT_DIR)

if __name__ == "__main__":
    main()

