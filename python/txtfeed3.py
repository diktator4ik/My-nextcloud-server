import subprocess
import json
import os
import time
import re

def log(msg):
    print(f"[{time.strftime('%H:%M:%S')}] 🌀 {msg}", flush=True)

def parse_with_llama_json(text_file, model_path, output_json_path):
    log(f"📖 Reading file: {text_file}")
    with open(text_file, 'r', encoding="utf-8") as f:
        document_text = f.read()

    log(f"🧠 Preparing JSON schema and prompt...")
    json_schema = json.dumps({
        "type": "object",
        "properties": {
            "question": {"type": "string"},
            "variants": {"type": "array", "items": {"type": "string"}},
            "answer": {"type": "string"},
            "explanation": {"type": "string"}
        },
        "required": ["question", "variants", "answer", "explanation"]
    })

    prompt = (
            f"""Return ONLY valid JSON, with no text before or after. Do not include markdown formatting or explanations.You are USMLE teaching bot. Extract USMLE-style question from the text below and respond. JSON must contain question, options, answer, explanation. This is USMLE test: {document_text}"""
            )

    cmd = [
        "/home/diktator4ik/lamacpp/llama.cpp/build/bin/llama-cli",
        "-m", model_path,
        "--prompt", prompt,
        "-t", "8",
        "--temp", "0.3",
    ]

    log("🚀 Launching LLaMA process...")
    start = time.time()

    try:
        result = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=1200  # 5 хвилин тайм-аут
        )
    except subprocess.TimeoutExpired:
        log("⏱️ Timeout! Model took too long.")
        data = {"error": "timeout", "file": text_file}
        output = ""
    else:
        output = result.stdout.strip()
        log(f"🕓 LLaMA finished in {round(time.time() - start, 1)}s")

    if output:
        log(f"📤 Model raw output (first 500 chars):\n{output[:500]}")

    # пробуємо розпарсити JSON
    try:
        json_match = re.search(r"\{.*\}", output, re.DOTALL)
        if json_match:
            json_str = json_match.group()
            data = json.loads(output)
            log("✅ JSON parsed successfully!")
        else:
            raise json.JSONDecodeError("NoJSON found", utput, 0)
    except json.JSONDecodeError:
        log("⚠️ Invalid JSON output. Saving raw text.")
        data = {"error": "Invalid JSON", "raw_output": output[:2000]}

    os.makedirs(os.path.dirname(output_json_path), exist_ok=True)
    with open(output_json_path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    log(f"💾 JSON saved to: {output_json_path}\n")

def batch_parse_all(input_dir, model_path, output_dir):
    log(f"🔍 Scanning for TXT files in {input_dir}...")
    txt_files = sorted([f for f in os.listdir(input_dir) if f.endswith(".txt")])

    if not txt_files:
        log("😢 No .txt files found!")
        return

    for idx, txt_file in enumerate(txt_files, 1):
        log(f"\n🚧 [{idx}/{len(txt_files)}] Processing {txt_file}")
        input_path = os.path.join(input_dir, txt_file)
        output_path = os.path.join(output_dir, txt_file.replace(".txt", ".json"))

        try:
            parse_with_llama_json(input_path, model_path, output_path)
        except Exception as e:
            log(f"💥 Error while processing {txt_file}: {e}")

    log("🎉 All files processed!")

if __name__ == "__main__":
    batch_parse_all(
        "/home/diktator4ik/python/txt",
        "/home/diktator4ik/lamacpp/llama.cpp/build/huggingmodels/mistral",
        "/home/diktator4ik/python/output"
    )

