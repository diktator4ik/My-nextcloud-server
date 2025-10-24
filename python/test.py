import subprocess
import json
import os
import time
import re


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

    prompt = (
f"""Return ONLY valid JSON, with no text before or after. Do not include markdown formatting or explanations.You are USMLE teaching bot. Extract USMLE-style question from the text below and respond Text:"""
            )

    cmd = [
        "/home/diktator4ik/lamacpp/llama.cpp/build/bin/llama-cli",
        "-i",
        "-m", model_path,
        "--prompt", prompt,
        "-t", "8",
        "-j", json_schema,
        "--temp", "0.1",
        "--verbose-prompt", 
    ]
    result = subprocess.run(
            cmd,
            input=document_text,
            capture_output=True,
            text=True,
        )
    output = result.stdout.strip()
    print(output)
if __name__ == "__main__":
    parse_with_llama_json(
        "/home/diktator4ik/python/txt/ophtalmology1_part_1.txt",
        "/home/diktator4ik/lamacpp/llama.cpp/build/huggingmodels/mistral",
        "/home/diktator4ik/python/output"
    )
