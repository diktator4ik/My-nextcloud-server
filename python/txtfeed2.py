import subprocess
import json
import os

def parse_with_llama_json(text_file, model_path, output_json_path):
    with open(text_file, 'r', encoding="utf-8") as f:
        document_text = f.read()

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

    prompt = f"You are USMLE teaching bot. Extract qestions from USMLE qestion bank from the text below and respond USMLE - like question, options, answer, explanation in JSON format without any other format than JSON, answer using this JSON schema {json_schema}:\n\n{document_text[:4000]}"

    cmd = [
        "/home/diktator4ik/lamacpp/llama.cpp/build/bin/llama-cli",
        "-m", model_path,
        "--prompt", prompt,
        "-t", "8",
        "-ngl", "20",
        "-c", "4096",
        "--temp", "0.2"
    ]

    result = subprocess.run(cmd, input=prompt, capture_output=True, text=True)
    output = result.stdout.strip()
    print(output)
    try:
        data = json.loads(output)
    except json.JSONDecodeError:
        data = {"error": "Invalid JSON", "raw_output": output}

    os.makedirs(os.path.dirname(output_json_path), exist_ok=True)
    with open(output_json_path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    print(f"✅ JSON saved: {output_json_path}")

if __name__ == "__main__":
    parse_with_llama_json(
        "/home/diktator4ik/python/txt/ophtalmology1_part_1.txt",
        "/home/diktator4ik/lamacpp/llama.cpp/build/huggingmodels/guanaco",
        "/home/diktator4ik/python/output/ophtalmology1_part_1.json"
    )

