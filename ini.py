import time
import sys
import random

# Kode warna ANSI
class Colors:
    RED = '\033[91m'
    GREEN = '\033[92m'
    YELLOW = '\033[93m'
    BLUE = '\033[94m'
    MAGENTA = '\033[95m'
    CYAN = '\033[96m'
    WHITE = '\033[97m'
    RESET = '\033[0m'
    BOLD = '\033[1m'
    GRAY = '\033[90m'

def print_slow(text, delay=0.03):
    """Print text dengan efek ketikan"""
    for char in text:
        sys.stdout.write(char)
        sys.stdout.flush()
        time.sleep(delay)
    print()

def print_glitch(text, delay=0.05):
    """Print dengan efek glitch"""
    glitch_chars = ['#', '@', '$', '%', '&', '*']
    for char in text:
        if random.random() > 0.9:
            sys.stdout.write(random.choice(glitch_chars))
            sys.stdout.flush()
            time.sleep(delay/2)
            sys.stdout.write('\b')
        sys.stdout.write(char)
        sys.stdout.flush()
        time.sleep(delay)
    print()

def clear_screen():
    """Clear terminal"""
    print("\n" * 50)

# ASCII Art Topeng Anonymous / Guy Fawkes
anonymous_mask = f"""
{Colors.WHITE}{Colors.BOLD}
                 ░░░░░░░░░░░░░░░░░░░░░
              ░░░░░░░░░░░░░░░░░░░░░░░░░░░
            ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
           ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
          ░░░░░░░{Colors.GRAY}████████{Colors.WHITE}░░░{Colors.GRAY}████████{Colors.WHITE}░░░░░░░
         ░░░░░░{Colors.GRAY}███{Colors.RED}▓▓▓{Colors.GRAY}███{Colors.WHITE}░░{Colors.GRAY}███{Colors.RED}▓▓▓{Colors.GRAY}███{Colors.WHITE}░░░░░
         ░░░░░░{Colors.GRAY}███████{Colors.WHITE}░░░░░{Colors.GRAY}███████{Colors.WHITE}░░░░░░
         ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
          ░░░░░░░░░░░{Colors.GRAY}███████{Colors.WHITE}░░░░░░░░░░░░
          ░░░░░░░░░{Colors.GRAY}███████████{Colors.WHITE}░░░░░░░░░░
           ░░░░░░░{Colors.GRAY}█████████████{Colors.WHITE}░░░░░░░░
            ░░░░░{Colors.GRAY}███████████████{Colors.WHITE}░░░░░░
             ░░░░{Colors.GRAY}█████████████{Colors.WHITE}░░░░░░
              ░░░░{Colors.GRAY}███████████{Colors.WHITE}░░░░░░
                ░░░{Colors.GRAY}███████{Colors.WHITE}░░░░░
                  ░░░░░░░░░░
{Colors.RESET}
"""

# Pesan Anonymous style
messages = [
    f"{Colors.RED}{Colors.BOLD}[!] SYSTEM BREACH DETECTED{Colors.RESET}",
    f"{Colors.GREEN}[+] Initializing Anonymous Protocol...{Colors.RESET}",
    f"{Colors.CYAN}[*] Connecting to Darknet...{Colors.RESET}",
    f"{Colors.YELLOW}[*] Spoofing IP Address...{Colors.RESET}",
    f"{Colors.GREEN}[+] VPN Tunnel: ACTIVE{Colors.RESET}",
    f"{Colors.CYAN}[*] Scanning Target Network...{Colors.RESET}",
    f"{Colors.YELLOW}[*] Exploiting Vulnerability: CVE-2024-XXXX{Colors.RESET}",
    f"{Colors.GREEN}[+] Root Access: GRANTED{Colors.RESET}",
    f"{Colors.RED}[!] Firewall: BYPASSED{Colors.RESET}",
    f"{Colors.MAGENTA}[*] Downloading Classified Data...{Colors.RESET}",
    f"{Colors.GREEN}{Colors.BOLD}[+] MISSION ACCOMPLISHED!{Colors.RESET}",
]

def main():
    clear_screen()
    
    # Header
    print(f"\n{Colors.RED}{Colors.BOLD}{'='*60}{Colors.RESET}")
    print_glitch(f"{Colors.WHITE}{Colors.BOLD}           W E   A R E   A N O N Y M O U S           {Colors.RESET}", delay=0.03)
    print(f"{Colors.RED}{Colors.BOLD}{'='*60}{Colors.RESET}\n")
    
    time.sleep(0.5)
    
    # Tampilkan topeng Anonymous
    print(anonymous_mask)
    
    time.sleep(0.5)
    
    # Tampilkan pesan hacker
    for msg in messages:
        print_slow(msg, delay=0.02)
        time.sleep(random.uniform(0.2, 0.5))
    
    # Footer
    print(f"\n{Colors.GRAY}{'─'*60}{Colors.RESET}")
    print(f"{Colors.CYAN}{Colors.BOLD}      We are Anonymous. We are Legion.{Colors.RESET}")
    print(f"{Colors.CYAN}{Colors.BOLD}      We do not forgive. We do not forget.{Colors.RESET}")
    print(f"{Colors.CYAN}{Colors.BOLD}           Expect us... 👁️{Colors.RESET}")
    print(f"{Colors.GRAY}{'─'*60}{Colors.RESET}\n")
    
    # Matrix effect
    print(f"{Colors.GREEN}", end="")
    for _ in range(3):
        for _ in range(40):
            sys.stdout.write(random.choice(['0', '1']))
            sys.stdout.flush()
            time.sleep(0.01)
        print()
    print(f"{Colors.RESET}")

if __name__ == "__main__":
    main()