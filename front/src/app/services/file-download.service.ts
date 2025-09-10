import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class FileDownloadService {

  constructor() { }

  /**
   * Télécharge un fichier avec le contenu et le type MIME spécifiés
   */
  downloadFile(content: string, mimeType: string, filename: string): void {
    const blob = new Blob([content], { type: `${mimeType};charset=utf-8;` });

    const url = URL.createObjectURL(blob);

    const link = this.createDownloadLink(url, filename);

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    URL.revokeObjectURL(url);
  }

  /**
   * Crée un élément de lien de téléchargement
   */
  private createDownloadLink(url: string, filename: string): HTMLAnchorElement {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.style.display = 'none';
    return link;
  }

  /**
   * Télécharge un fichier CSV
   */
  downloadCsv(content: string, filename?: string): void {
    const defaultFilename = filename || `export_${new Date().toISOString().split('T')[0]}.csv`;
    this.downloadFile(content, 'text/csv', defaultFilename);
  }

  /**
   * Télécharge un fichier JSON
   */
  downloadJson(content: any, filename?: string): void {
    const jsonContent = JSON.stringify(content, null, 2);
    const defaultFilename = filename || `export_${new Date().toISOString().split('T')[0]}.json`;
    this.downloadFile(jsonContent, 'application/json', defaultFilename);
  }
}






