#!/usr/bin/env python3
"""
Export monthly expenses by category to CSV.

Usage:
    python export_expenses.py --api-key YOUR_API_KEY --email YOUR_EMAIL --base-url https://your-app.com

Arguments:
    --api-key       : Your API key for authentication
    --email         : User email
    --base-url      : Base URL of your app (default: http://localhost:8000)
    --month         : Month to export (1-12, default: current month)
    --year          : Year to export (default: current year)
    --no-verify-ssl : Disable SSL certificate verification (for development)
    --timeout       : Request timeout in seconds (default: 30)
    --verbose, -v   : Show verbose output for debugging
"""

import argparse
import requests
import pandas as pd
from datetime import datetime
from pathlib import Path
import sys
import urllib3
import socket
from urllib.parse import urlparse


def check_dns_resolution(hostname: str) -> bool:
    """Check if DNS can resolve the hostname."""
    try:
        socket.gethostbyname(hostname)
        return True
    except socket.gaierror:
        return False


def fetch_expenses(base_url: str, api_key: str, email: str, year: int, month: int, verify_ssl: bool = True, timeout: int = 30, verbose: bool = False) -> dict:
    """Fetch expenses from the API."""
    url = f"{base_url}/api/expenses-by-category"
    params = {
        "year": year,
        "month": month,
        "email": email
    }
    headers = {
        "X-API-Key": api_key
    }

    # Parse URL to get hostname
    parsed_url = urlparse(base_url)
    hostname = parsed_url.hostname
    
    if hostname:
        if verbose:
            print(f"Checking DNS resolution for: {hostname}")
        if not check_dns_resolution(hostname):
            print(f"\nDNS Resolution Failed: Cannot resolve '{hostname}'")
            print("\nPossible solutions:")
            print("1. Check if you're connected to the correct network/VPN")
            print("2. Verify the domain name is correct")
            print("3. Try using an IP address instead of domain name")
            print("4. Check /etc/hosts file for local domain mappings")
            print("5. Contact your network administrator if this is an internal domain")
            raise ConnectionError(f"DNS resolution failed for {hostname}")
        elif verbose:
            ip_address = socket.gethostbyname(hostname)
            print(f"DNS resolved to: {ip_address}")

    print(f"Requesting: {url}")
    print(f"Params: {params}")
    print(f"SSL Verification: {verify_ssl}")
    print(f"Timeout: {timeout}s")

    try:
        response = requests.get(url, params=params, headers=headers, verify=verify_ssl, timeout=timeout)
    except requests.exceptions.SSLError as e:
        print(f"SSL Error: {e}")
        print("Tip: If this is a development server with a self-signed certificate, try using --no-verify-ssl")
        raise
    except requests.exceptions.Timeout as e:
        print(f"Timeout Error: Request took longer than {timeout} seconds")
        raise
    except requests.exceptions.ConnectionError as e:
        print(f"Connection Error Details: {e}")
        print(f"Failed to connect to: {base_url}")
        print("\nTroubleshooting tips:")
        print("1. Check if the server is running and accessible")
        print("2. Verify the URL is correct")
        print("3. Check your network connection")
        print("4. If using HTTPS, verify SSL certificate is valid")
        print("5. Try accessing the URL in a browser first")
        raise

    # Debug: show response details
    print(f"Status Code: {response.status_code}")
    if verbose:
        print(f"Response Headers: {dict(response.headers)}")
        print(f"Full URL: {response.url}")

    if response.status_code != 200:
        print(f"\nError Response ({response.status_code}):")
        # Try to decode response
        try:
            error_text = response.text
            if error_text:
                # Show first 1000 chars of error
                print(error_text[:1000])
                if len(error_text) > 1000:
                    print(f"... (truncated, total length: {len(error_text)} chars)")
            else:
                print("(Empty response body)")
        except Exception as e:
            print(f"Could not decode response: {e}")
            print(f"Response content: {response.content[:500]}")
        
        if response.status_code == 404:
            print("\n404 Not Found - Possible issues:")
            print(f"1. Check if the route exists: {url}")
            print("2. Verify the API prefix is correct (/api/)")
            print("3. Check if the server is configured to handle API routes")
            print("4. Try accessing the base URL in a browser to verify server is running")
            print("5. Check Laravel route list: php artisan route:list | grep expenses")
        
        response.raise_for_status()

    # Check if response is empty
    if not response.text:
        raise ValueError("Empty response from API")

    try:
        return response.json()
    except requests.exceptions.JSONDecodeError as e:
        print(f"Response Text: {response.text[:500]}")  # Show first 500 chars
        raise ValueError(f"Invalid JSON response: {e}")


def export_to_csv(data: dict, output_path: str) -> str:
    """Export category data to CSV using pandas."""
    categories = data.get("categories", [])

    if not categories:
        print("No expenses found for the specified period.")
        return None

    # Extract only label and totalRSD
    rows = [
        {
            "Category": cat["label"],
            "Total (RSD)": cat["totalRSD"]
        }
        for cat in categories
    ]

    # Create DataFrame
    df = pd.DataFrame(rows)

    # Ensure output directory exists
    output_dir = Path(output_path).parent
    output_dir.mkdir(parents=True, exist_ok=True)

    # Export to CSV
    df.to_csv(output_path, index=False)

    return output_path


def test_endpoint(base_url: str, verify_ssl: bool = True, timeout: int = 10) -> bool:
    """Test if the API endpoint is accessible."""
    test_url = f"{base_url}/api/expenses-by-category"
    print(f"\nTesting endpoint: {test_url}")
    
    try:
        response = requests.get(
            test_url, 
            params={"year": 2024, "month": 1, "email": "test@example.com"},
            headers={"X-API-Key": "test"},
            verify=verify_ssl,
            timeout=timeout
        )
        print(f"Test response status: {response.status_code}")
        if response.status_code == 401:
            print("✓ Endpoint exists (401 Unauthorized is expected without valid credentials)")
            return True
        elif response.status_code == 404:
            print("✗ Endpoint not found (404)")
            return False
        else:
            print(f"✓ Endpoint responded with status {response.status_code}")
            return True
    except Exception as e:
        print(f"✗ Endpoint test failed: {e}")
        return False


def main():
    parser = argparse.ArgumentParser(description="Export monthly expenses by category to CSV")
    parser.add_argument("--api-key", required=True, help="API key for authentication")
    parser.add_argument("--email", required=True, help="User email")
    parser.add_argument("--base-url", default="http://localhost:8000", help="Base URL of the app")
    parser.add_argument("--month", type=int, default=None, help="Month to export (1-12)")
    parser.add_argument("--year", type=int, default=None, help="Year to export")
    parser.add_argument("--no-verify-ssl", action="store_true", help="Disable SSL certificate verification (for development)")
    parser.add_argument("--timeout", type=int, default=30, help="Request timeout in seconds (default: 30)")
    parser.add_argument("--verbose", "-v", action="store_true", help="Show verbose output")
    parser.add_argument("--test-endpoint", action="store_true", help="Test if the API endpoint is accessible before making the actual request")

    args = parser.parse_args()

    # Use current month/year if not specified
    now = datetime.now()
    year = args.year or now.year
    month = args.month or now.month

    # Generate output path with current date
    current_date = now.strftime("%Y-%m-%d")
    output_path = f"/mnt/nas/Cha-ChingChronicles/expense_report_{current_date}.csv"

    # Disable SSL warnings if verification is disabled
    if args.no_verify_ssl:
        urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

    print(f"Fetching expenses for {month}/{year}...")
    if args.verbose:
        print(f"Base URL: {args.base_url}")
        print(f"Email: {args.email}")
        print(f"SSL Verification: {not args.no_verify_ssl}")

    # Test endpoint if requested
    if args.test_endpoint:
        if not test_endpoint(args.base_url, verify_ssl=not args.no_verify_ssl, timeout=args.timeout):
            print("\n⚠ Warning: Endpoint test failed. The API route might not be configured correctly.")
            print("You can still proceed, but the request will likely fail.")
            response = input("Continue anyway? (y/n): ")
            if response.lower() != 'y':
                sys.exit(1)

    try:
        # Fetch data from API
        data = fetch_expenses(
            args.base_url, 
            args.api_key, 
            args.email, 
            year, 
            month,
            verify_ssl=not args.no_verify_ssl,
            timeout=args.timeout,
            verbose=args.verbose
        )

        # Print summary
        summary = data.get("summary", {})
        print(f"Total expenses: {summary.get('totalExpensesRSD', 0):,.2f} RSD")
        print(f"Total transactions: {summary.get('totalTransactions', 0)}")
        print(f"Categories: {summary.get('categoriesCount', 0)}")

        # Export to CSV
        result_path = export_to_csv(data, output_path)

        if result_path:
            print(f"\nExported to: {result_path}")

    except requests.exceptions.HTTPError as e:
        print(f"\nAPI Error: {e.response.status_code}")
        if args.verbose:
            print(f"Response: {e.response.text}")
        else:
            print(f"Response: {e.response.text[:200]}")
        sys.exit(1)
    except requests.exceptions.ConnectionError as e:
        # Error message already printed in fetch_expenses
        sys.exit(1)
    except requests.exceptions.SSLError as e:
        # Error message already printed in fetch_expenses
        sys.exit(1)
    except requests.exceptions.Timeout as e:
        # Error message already printed in fetch_expenses
        sys.exit(1)
    except Exception as e:
        print(f"\nUnexpected Error: {e}")
        if args.verbose:
            import traceback
            traceback.print_exc()
        sys.exit(1)


if __name__ == "__main__":
    main()
