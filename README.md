# Ollama with PHP

Accessing [Ollama](https://ollama.com) with PHP. Main purpose is to connect the AI with Database (MySQL/MariaDB in my case), so you can 'talk' to your data with natural language. Make sure you have installed and running Ollama on your system.

For this test I'm using PHP 8.2^ and MariaDB.

Installation:
1. Install [Ollama](https://ollama.com)
2. Install your favorites LLM's model
3. Ready your database 
4. Edit `.env` to your system configuration
5. Have fun!

For LLM's model Ollama I'm using:
1. LLama3: from Meta
2. Qwen2: from Alibaba, good for other language beside English

TODO:
* Retrieving data from SQL database.
* Delete conversation
* Start New conversation
* Share/Export conversation
* Search conversation
* Dynamically show LLMs for selection, by retrieving the LLMs model with Ollama CLI
* (Long term) add serpapi for searching external data from internet
* (Long term) as Telegram Bot
* (Long term) as WhatsApp Bot

[Lazwardi](https://github.com/angween) | ...
