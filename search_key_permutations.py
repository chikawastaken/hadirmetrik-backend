from pathlib import Path
from itertools import permutations
from PIL import Image
import numpy as np

KEY = Path("recovered/key.txt").read_bytes()

assert len(KEY) == 29

arr = np.asarray(
    Image.open("poster_only.jpg").convert("RGB"),
    dtype=np.uint8
).reshape(-1, 3)

features = np.column_stack([
    (arr[:, 0] >> 0) & 1,
    (arr[:, 1] >> 0) & 1,
    (arr[:, 2] >> 0) & 1,
    (arr[:, 0] >> 1) & 1,
    (arr[:, 1] >> 1) & 1,
    (arr[:, 2] >> 1) & 1,
])

variants = {
    "raw": KEY,
    "reverse": KEY[::-1],
    "complement": bytes(b ^ 255 for b in KEY),
}

found = []

for perm in permutations(range(6)):
    bits = features[:, perm].reshape(-1)

    for order in ("big", "little"):
        packed = np.packbits(bits, bitorder=order).tobytes()

        for name, needle in variants.items():
            pos = packed.find(needle)

            if pos >= 0:
                found.append((perm, order, name, pos))

            # Periksa juga representasi komplemen bit secara keseluruhan.
            inverse = bytes(b ^ 255 for b in packed)
            pos = inverse.find(needle)

            if pos >= 0:
                found.append((perm, order, name + "_inverted", pos))

print("Permutations tested:", 720)
print("Matches:", len(found))

for item in found:
    print("MATCH:", item)

