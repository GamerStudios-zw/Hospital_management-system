import html
import re
import sys
from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.platypus import ListFlowable, ListItem, Paragraph, SimpleDocTemplate, Spacer


def build_styles():
    base = getSampleStyleSheet()
    styles = {
        "title": ParagraphStyle(
            "DocTitle",
            parent=base["Title"],
            fontName="Helvetica-Bold",
            fontSize=22,
            leading=26,
            textColor=colors.HexColor("#0f172a"),
            spaceAfter=8,
        ),
        "h2": ParagraphStyle(
            "DocH2",
            parent=base["Heading2"],
            fontName="Helvetica-Bold",
            fontSize=14,
            leading=18,
            textColor=colors.HexColor("#1e3a8a"),
            spaceBefore=10,
            spaceAfter=4,
        ),
        "h3": ParagraphStyle(
            "DocH3",
            parent=base["Heading3"],
            fontName="Helvetica-Bold",
            fontSize=12,
            leading=16,
            textColor=colors.HexColor("#1e40af"),
            spaceBefore=8,
            spaceAfter=3,
        ),
        "normal": ParagraphStyle(
            "DocNormal",
            parent=base["BodyText"],
            fontName="Helvetica",
            fontSize=10.5,
            leading=14.5,
            textColor=colors.HexColor("#111827"),
            spaceAfter=4,
        ),
        "code": ParagraphStyle(
            "DocCode",
            parent=base["BodyText"],
            fontName="Courier",
            fontSize=9.5,
            leading=13.5,
            textColor=colors.HexColor("#111827"),
            backColor=colors.HexColor("#f3f4f6"),
            leftIndent=8,
            rightIndent=8,
            spaceBefore=2,
            spaceAfter=2,
        ),
        "bullet": ParagraphStyle(
            "DocBullet",
            parent=base["BodyText"],
            fontName="Helvetica",
            fontSize=10.5,
            leading=14,
            textColor=colors.HexColor("#111827"),
        ),
    }
    return styles


def to_paragraph_text(text: str) -> str:
    escaped = html.escape(text.strip())
    # Minimal inline formatting support for `code`.
    escaped = re.sub(
        r"`([^`]+)`",
        r'<font name="Courier">\1</font>',
        escaped,
    )
    return escaped


def parse_markdown_to_story(md_text: str, styles: dict):
    story = []

    lines = md_text.splitlines()
    paragraph_buffer = []
    list_items = []
    list_mode = None  # None | "bullet" | "number"
    in_code_block = False

    def flush_paragraph():
        nonlocal paragraph_buffer
        if paragraph_buffer:
            text = " ".join(part.strip() for part in paragraph_buffer if part.strip())
            if text:
                story.append(Paragraph(to_paragraph_text(text), styles["normal"]))
            paragraph_buffer = []

    def flush_list():
        nonlocal list_items, list_mode
        if list_items:
            bullet_type = "bullet" if list_mode == "bullet" else "1"
            flow_items = [ListItem(Paragraph(to_paragraph_text(item), styles["bullet"])) for item in list_items]
            story.append(
                ListFlowable(
                    flow_items,
                    bulletType=bullet_type,
                    start="1",
                    leftIndent=14,
                    bulletFontName="Helvetica",
                    bulletFontSize=9,
                    spaceBefore=2,
                    spaceAfter=4,
                )
            )
        list_items = []
        list_mode = None

    for raw in lines:
        line = raw.rstrip("\n")
        stripped = line.strip()

        if stripped.startswith("```"):
            flush_paragraph()
            flush_list()
            in_code_block = not in_code_block
            continue

        if in_code_block:
            story.append(Paragraph(html.escape(line if line else " "), styles["code"]))
            continue

        if not stripped:
            flush_paragraph()
            flush_list()
            story.append(Spacer(1, 1.6 * mm))
            continue

        if stripped.startswith("# "):
            flush_paragraph()
            flush_list()
            story.append(Paragraph(to_paragraph_text(stripped[2:]), styles["title"]))
            continue

        if stripped.startswith("## "):
            flush_paragraph()
            flush_list()
            story.append(Paragraph(to_paragraph_text(stripped[3:]), styles["h2"]))
            continue

        if stripped.startswith("### "):
            flush_paragraph()
            flush_list()
            story.append(Paragraph(to_paragraph_text(stripped[4:]), styles["h3"]))
            continue

        if stripped.startswith("- "):
            flush_paragraph()
            if list_mode not in (None, "bullet"):
                flush_list()
            list_mode = "bullet"
            list_items.append(stripped[2:].strip())
            continue

        if re.match(r"^\d+\.\s+", stripped):
            flush_paragraph()
            if list_mode not in (None, "number"):
                flush_list()
            list_mode = "number"
            list_items.append(re.sub(r"^\d+\.\s+", "", stripped).strip())
            continue

        # Regular paragraph line
        flush_list()
        paragraph_buffer.append(stripped)

    flush_paragraph()
    flush_list()
    return story


def main():
    if len(sys.argv) != 3:
        print("Usage: python scripts/generate_system_overview_pdf.py <input.md> <output.pdf>")
        raise SystemExit(1)

    input_path = Path(sys.argv[1])
    output_path = Path(sys.argv[2])

    if not input_path.exists():
        print(f"Input file not found: {input_path}")
        raise SystemExit(1)

    md_text = input_path.read_text(encoding="utf-8")
    styles = build_styles()
    story = parse_markdown_to_story(md_text, styles)

    doc = SimpleDocTemplate(
        str(output_path),
        pagesize=A4,
        leftMargin=18 * mm,
        rightMargin=18 * mm,
        topMargin=16 * mm,
        bottomMargin=16 * mm,
        title="Hospital Management System Overview and Tech Stack",
        author="HMS Project",
    )

    doc.build(story)
    print(f"PDF generated: {output_path}")


if __name__ == "__main__":
    main()

