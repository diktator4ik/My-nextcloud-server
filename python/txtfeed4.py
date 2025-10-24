import subprocess
import json
import os
import time
import re
import signal

def log(msg):
    print(f"[{time.strftime('%H:%M:%S')}] 🌀 {msg}", flush=True)

def parse_with_llama_json(text_file, model_path, output_json_path):
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
Extract the USMLE-style question from the following text and return ONLY valid JSON with no additional text.

Required JSON format:
{{
  "question": "the question text",
  "variants": ["option A", "option B", "option C", ...],
  "answer": "correct answer letter",
  "explanation": "explanation text"
}}

Text to process:
{document_text}

Return ONLY the JSON:
"""

    cmd = [
        "/home/diktator4ik/lamacpp/llama.cpp/build/bin/llama-cli",
        "-m", model_path,
        "--prompt", prompt,
        "-t", "8",
        "--temp", "0.3",
        "--ctx-size", "2048"  # Added context size limit
    ]

    log("🚀 Launching LLaMA process...")
    log("📝 Real-time LLM output:")
    print("─" * 80)
    start = time.time()

    output = ""
    process = None
    
    def timeout_handler(signum, frame):
        raise TimeoutError("Process timeout")
    
    # Set timeout handler
    signal.signal(signal.SIGALRM, timeout_handler)
    signal.alarm(300)  # 5 minute timeout
    
    try:
        process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            bufsize=1,
            universal_newlines=True
        )

        output_lines = []
        # Read with timeout protection
        while True:
            line = process.stdout.readline()
            if not line:
                break
            print(line, end='', flush=True)
            output_lines.append(line)
            
            # Stop if we detect JSON start and end (basic completion detection)
            current_output = ''.join(output_lines)
            if re.search(r'\{.*\}', current_output, re.DOTALL):
                log("✅ Detected complete JSON output, stopping process...")
                process.terminate()
                break

        output = ''.join(output_lines)
        signal.alarm(0)  # Cancel timeout
        
        # Wait a bit for process to terminate
        try:
            process.wait(timeout=10)
        except subprocess.TimeoutExpired:
            process.kill()

    except TimeoutError:
        log("⏱️  Timeout! Process took too long.")
        if process:
            process.kill()
        output = output or "Timeout occurred"
    except Exception as e:
        log(f"💥 Process error: {e}")
        output = output or f"Error: {e}"
    finally:
        signal.alarm(0)  # Ensure timeout is cleared

    log(f"🕓 Process completed in {round(time.time() - start, 1)}s")
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

def batch_parse_all(input_dir, model_path, output_dir):
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
            result = parse_with_llama_json(input_path, model_path, output_path)
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
        "/home/diktator4ik/lamacpp/llama.cpp/build/huggingmodels/mistral", 
        "/home/diktator4ik/python/output"
    )
