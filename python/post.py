import requests

prompt=input()

url = "http://192.168.1.20:8080/completion"
payload = {
    "prompt": f"{prompt}",
    "n_predict": 512
}

response = requests.post(url, json=payload) # Use the 'json' parameter to automatically set headers and format data:cite[2]:cite[9]

print(response.text) # Print the raw text response
print(response.json()) # Alternatively, if the response is JSON, use .json() to parse it:cite[3]:cite[9]
