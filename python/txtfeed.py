import subprocess
import os

def interactive_document_query(text_file, model_path):
    # Read the document once
    with open(text_file, 'r') as f:
        document_text = f.read()

    print(f"Document '{os.path.basename(text_file)}' loaded. You can now ask questions about it.")
    print("Type 'quit' to exit.\n")

    while True:
        query = input("Your question: ").strip()

        if query.lower() in ['quit', 'exit', 'q']:
            break

        # Create prompt
        prompt = f"""Document Context:
{document_text[:5000]}

Question: {query}

Answer:"""

        # Run llama-cli
        cmd = [
            "/home/diktator4ik/lamacpp/llama.cpp/build/bin/llama-cli",
            "-m", model_path,
            "-t", "8",
            "-ngl", "20",
            "-c", "4096",
            "--temp", "0.1"
        ]

        result = subprocess.run(cmd, input=prompt, capture_output=True, text=True)
        print(f"\nAnswer: {result.stdout}\n")

# Usage with absolute paths:
if __name__ == "__main__":
    interactive_document_query(
        "/home/diktator4ik/python/ophtalmology1.txt", 
        "/home/diktator4ik/lamacpp/llama.cpp/build/huggingmodels/qwen3"
    )
