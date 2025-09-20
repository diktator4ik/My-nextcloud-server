import requests
import json


API_URL = "http://192.168.1.20.nip.io/api/v1/chat/completions"
API_KEY = "sk-01661e7f587844d4be9214e5a4334f3f"  
MODEL = "llama3.1:latest"  

headers = {
    "Content-Type": "application/json",
    "Authorization": f"Bearer {API_KEY}"
}

def ask_model(prompt):
    data = {
        "model": MODEL,
        "messages": [
            {"role": "user", "content": prompt}
        ],
        "temperature": 0.7,
        "max_tokens": 200,
        "keep alive": 1
    }
    response = requests.post(API_URL, headers=headers, json=data)

    if response.status_code != 200:
        print(f"Error {response.status_code}: {response.text}")
        return None

    result = response.json()
    try:
        return result["choices"][0]["message"]["content"]
    except (KeyError, IndexError):
        return json.dumps(result, indent=4, ensure_ascii=False)

def main():
    print("🚀 Open-WebUI CLI Chat (type 'exit' to quit)\n")
    while True:
        prompt = input("input: ")
        if prompt.lower() in ("exit", "quit"):
            break

        reply = ask_model(prompt)
        print(f"AI: {reply}\n")

if __name__ == "__main__":
    main()

