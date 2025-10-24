import fitz  # PyMuPDF
import subprocess

def extract_text(pdf_path):
    doc = fitz.open(pdf_path)
    return "\n".join(page.get_text() for page in doc)

def ask_llm(question, context):
    result = subprocess.run(
        ["/opt/llama.cpp/llama-cli", "--model", "models/llama-3-8b-q4.gguf", "--prompt", f"{question}\n\nContext:\n{context}"],
        capture_output=True, text=True
    )
    return result.stdout

if __name__ == "__main__":
    text = extract_text("test.pdf")
    print(ask_llm("Summarize this text:", text[:2000]))  # шматок для демо

