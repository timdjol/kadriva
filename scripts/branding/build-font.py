"""Build Kadriva Sans from the OFL Manrope source. Requires fonttools[woff]."""
from pathlib import Path
from fontTools.ttLib import TTFont
from fontTools.varLib.instancer import instantiateVariableFont
from fontTools.pens.ttGlyphPen import TTGlyphPen
from fontTools.pens.transformPen import TransformPen

root = Path(__file__).resolve().parents[2]
for weight in (400, 600, 750, 850):
    font = instantiateVariableFont(TTFont(Path(__file__).with_name('Manrope-source.ttf')), {'wght': weight}, inplace=True)
    # A slightly narrower rhythm, retaining all source kerning and language coverage.
    glyphs = font.getGlyphSet()
    replacements = {}
    for name in font.getGlyphOrder():
        pen = TTGlyphPen(glyphs)
        glyphs[name].draw(TransformPen(pen, (.97, 0, 0, 1, 0, 0)))
        replacements[name] = pen.glyph()
    for name, glyph in replacements.items():
        font['glyf'][name] = glyph
        advance, bearing = font['hmtx'][name]
        font['hmtx'][name] = (round(advance * .97), round(bearing * .97))
    # Bespoke split-arm K/k: the diagonal stops short of the vertical stem,
    # echoing the separated frame in the symbol. Includes Cyrillic К/к.
    cmap = font.getBestCmap()
    for char in 'KkКк':
        name = cmap[ord(char)]
        advance = font['hmtx'][name][0]
        height = font['OS/2'].sCapHeight if char.isupper() else font['OS/2'].sxHeight
        left, right = round(advance * .09), round(advance * .94)
        stem = round(height * (.075 + weight / 11500))
        joint = left + stem + round(height * .055)
        mid = round(height * .46)
        pen = TTGlyphPen(None)
        for points in [
            [(left, 0), (left, height), (left + stem, height), (left + stem, 0)],
            [(joint, mid), (right - stem, height), (right + 8, height), (joint + stem, mid)],
            [(joint, mid), (joint + stem, mid), (right + 8, 0), (right - stem - 5, 0)],
        ]:
            pen.moveTo(points[0])
            for point in points[1:]: pen.lineTo(point)
            pen.closePath()
        font['glyf'][name] = pen.glyph()
        font['hmtx'][name] = (advance, left)
    family = 'Kadriva Sans'
    style = {400: 'Regular', 600: 'SemiBold', 750: 'Bold', 850: 'ExtraBold'}[weight]
    for record in font['name'].names:
        values = {1: family, 2: style, 3: f'KadrivaSans-{style}-1.000', 4: f'{family} {style}', 6: f'KadrivaSans-{style}', 16: family, 17: style}
        if record.nameID in values:
            record.string = values[record.nameID].encode(record.getEncoding())
    font['OS/2'].usWeightClass = weight
    font.flavor = 'woff2'
    font.save(root / f'public/fonts/kadriva/kadriva-sans-{weight}.woff2')
