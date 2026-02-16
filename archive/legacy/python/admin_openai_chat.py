#!/usr/bin/env python3
import argparse
import os
import sys

from openai import OpenAI


def load_env_file(path: str) -> None:
    if not os.path.isfile(path):
        return
    try:
        with open(path, "r", encoding="utf-8", errors="ignore") as f:
            for raw in f:
                line = raw.strip()
                if not line or line.startswith("#") or "=" not in line:
                    continue
                name, value = line.split("=", 1)
                name = name.strip()
                value = value.strip()
                if (value.startswith('"') and value.endswith('"')) or (value.startswith("'") and value.endswith("'")):
                    value = value[1:-1]
                if name and name not in os.environ:
                    os.environ[name] = value
    except Exception:
        return


def get_api_key() -> str:
    project_root = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
    load_env_file(os.path.join(project_root, ".env"))
    return os.environ.get("OPENAI_API_KEY") or os.environ.get("OPENAIAPI") or ""


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--model", default="gpt-4.1-mini")
    parser.add_argument("--prompt", required=True)
    args = parser.parse_args()

    api_key = get_api_key()
    if not api_key:
        print("OPENAI_API_KEY/OPENAIAPI not found.", file=sys.stderr)
        return 2

    client = OpenAI(api_key=api_key)
    response = client.chat.completions.create(
        model=args.model,
        messages=[
            {"role": "system", "content": "You are a helpful assistant for a real-estate data parsing MVP. Be concise and practical."},
            {"role": "user", "content": args.prompt},
        ],
        temperature=0.2,
    )

    print(response.choices[0].message.content or "")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
