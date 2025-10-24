import json
import os
import time
import re
import requests

def log(msg):
    print(f"[{time.strftime('%H:%M:%S')}] 🌀 {msg}", flush=True)

def parse_with_llama_api(text_file, api_url, output_json_path):
    log(f"📖 Reading file: {text_file}")
    try:
        with open(text_file, 'r', encoding="utf-8") as f:
            document_text = f.read()
    except Exception as e:
        log(f"💥 Error reading file: {e}")
        return

    log(f"🧠 Preparing JSON schema and prompt...")

    # Improved prompt for better JSON generation
    prompt = f"""
You are an expert USMLE question extractor.
Your task is to read a block of text (from a PDF page) and identify:
- The question text (the main clinical scenario and ending with a '?')
- The answer options (variants)
- The correct answer letter (if mentioned)
- The explanation (why this answer is correct)

Rules:
1. Extract only ONE question per block.
2. If multiple pages repeat the same question, merge them into one complete version.
3. Return ONLY valid JSON in the format:

{{
  "topic": "for example ophtalmology"
  "question": "...",
  "variants": ["A. ...", "B. ...", "C. ...", ...],
  "answer": ,
  "explanation": "..."
}}

Do not include any text outside of JSON. Do not write markdown or extra commentary.

Text to process:
{document_text}
"""
    payload = {
        "prompt": prompt,
        "n_predict": 1024,
        "temperature": 0.1
    }

    log("🚀 Sending request to LLaMA API...")
    log("📝 Waiting for LLM response...")
    print("─" * 80)
    start = time.time()

    output = ""

    try:
        response = requests.post(f"{api_url}/completion", json=payload, timeout=300)
        response.raise_for_status()
        
        result = response.json()
        output = result.get("content", "")
        
        print(output, flush=True)
        log(f"✅ Received response from API")

    except requests.exceptions.Timeout:
        log("⏱️  Timeout! API request took too long.")
        output = "Timeout occurred"
    except requests.exceptions.RequestException as e:
        log(f"💥 API request error: {e}")
        output = f"Error: {e}"
    except Exception as e:
        log(f"💥 Unexpected error: {e}")
        output = f"Error: {e}"

    log(f"🕓 API request completed in {round(time.time() - start, 1)}s")
    print("─" * 80)

    if output:
        log(f"📤 Model raw output length: {len(output)} characters")

    # Improved JSON parsing with multiple fallbacks
    data = {}
    json_str = None

    # Method 1: Try to find JSON with regex
    json_match = re.search(r'\{[\s\S]*\}', output)
    if json_match:
        json_str = json_match.group()
        try:
            data = json.loads(json_str)
            log("✅ JSON parsed successfully with regex method!")
        except json.JSONDecodeError as e:
            log(f"⚠️ Regex JSON parsing failed: {e}")
            json_str = None

    # Method 2: If regex failed, try to clean the output and parse directly
    if not json_str:
        try:
            # Remove any text before first { and after last }
            cleaned = re.sub(r'^[^{]*', '', output)
            cleaned = re.sub(r'[^}]*$', '', cleaned)
            data = json.loads(cleaned)
            log("✅ JSON parsed successfully with cleaning method!")
        except json.JSONDecodeError as e:
            log(f"⚠️ Cleaned JSON parsing failed: {e}")

            # Method 3: Try to extract just the content between first { and last }
            try:
                start_idx = output.find('{')
                end_idx = output.rfind('}') + 1
                if start_idx != -1 and end_idx != -1:
                    json_str = output[start_idx:end_idx]
                    data = json.loads(json_str)
                    log("✅ JSON parsed successfully with substring method!")
                else:
                    raise ValueError("No JSON boundaries found")
            except Exception as e:
                log(f"⚠️ All JSON parsing methods failed: {e}")
                data = {
                    "error": "JSON parsing failed",
                    "raw_output_preview": output[:1000],
                    "file": text_file
                }

    # Ensure required fields exist
    required_fields = ["question", "variants", "answer", "explanation"]
    for field in required_fields:
        if field not in data:
            data[field] = f"Missing {field}"

    # Save results
    os.makedirs(os.path.dirname(output_json_path), exist_ok=True)
    with open(output_json_path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    log(f"💾 JSON saved to: {output_json_path}\n")
    return data

def batch_parse_all(input_dir, api_url, output_dir):
    log(f"🔍 Scanning for TXT files in {input_dir}...")
    txt_files = sorted([f for f in os.listdir(input_dir) if f.endswith(".txt")])

    if not txt_files:
        log("😢 No .txt files found!")
        return

    successful = 0
    failed = 0

    for idx, txt_file in enumerate(txt_files, 1):
        log(f"\n🚧 [{idx}/{len(txt_files)}] Processing {txt_file}")
        input_path = os.path.join(input_dir, txt_file)
        output_path = os.path.join(output_dir, txt_file.replace(".txt", ".json"))

        try:
            result = parse_with_llama_api(input_path, api_url, output_path)
            if "error" not in result:
                successful += 1
            else:
                failed += 1
        except Exception as e:
            log(f"💥 Error while processing {txt_file}: {e}")
            failed += 1

        # Small delay between processing
        time.sleep(2)

    log(f"🎉 Processing complete! Successful: {successful}, Failed: {failed}")

if __name__ == "__main__":
    batch_parse_all(
        "/home/diktator4ik/python/txt",
        "http://192.168.1.20:8080",
        "/home/diktator4ik/python/output"
    )
