<?php

namespace Database\Seeders;

use App\Models\LearningAchievement;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * CP (Capaian Pembelajaran) seed from the official BSKAP Kurikulum
 * Merdeka documents (Keputusan Kepala BSKAP 032/H/KR/2022), condensed
 * per subject × fase × elemen for the SD subject list in SubjectSeeder.
 * Rows stay editable in the Kurikulum resource; TP is authored by
 * teachers, not seeded (doc 06 §5).
 */
class LearningAchievementSeeder extends Seeder
{
    /**
     * Subject code → fase → elemen → CP description.
     *
     * @var array<string, array<string, array<string, string>>>
     */
    private array $data = [
        'PKN' => [
            'A' => [
                'Pancasila' => 'Peserta didik mampu mengenal dan menceritakan simbol serta sila-sila dalam Pancasila dan menunjukkan sikap serta perilaku sesuai dengan nilai-nilai Pancasila di lingkungan keluarga dan sekolah.',
                'UUD NRI Tahun 1945' => 'Peserta didik mampu mengenal makna proklamasi kemerdekaan serta meneladani jasa para pahlawan bangsa.',
                'Bhinneka Tunggal Ika' => 'Peserta didik mampu mengenal keberagaman agama, suku bangsa, dan budaya di lingkungan sekitar serta menunjukkan sikap menghargai keberagaman.',
                'NKRI' => 'Peserta didik mampu menceritakan pengalaman menghormati sesama, mengenal lambang negara dan lagu nasional, serta menunjukkan rasa sayang terhadap tanah air.',
            ],
            'B' => [
                'Pancasila' => 'Peserta didik mampu menceritakan hubungan antarsila dalam Pancasila, menerapkan nilai-nilainya dalam perilaku sehari-hari, terlibat dalam pengambilan keputusan bersama, dan bertanggung jawab atas komitmen yang disepakati.',
                'UUD NRI Tahun 1945' => 'Peserta didik mampu menjelaskan penerapan nilai-nilai Pancasila sebagai pedoman berperilaku serta memahami aturan dalam kehidupan berbangsa dan bernegara.',
                'Bhinneka Tunggal Ika' => 'Peserta didik mampu mengidentifikasi dan menghargai keberagaman suku, agama, ras, dan budaya serta bekerja sama dalam keberagaman di lingkungan keluarga, sekolah, dan masyarakat.',
                'NKRI' => 'Peserta didik mampu memahami dasar pemikiran terbentuknya NKRI dan mempraktikkan pengamalan nilai-nilai Pancasila dalam kehidupan sehari-hari.',
            ],
            'C' => [
                'Pancasila' => 'Peserta didik mampu menganalisis dan mengevaluasi pengalaman penerapan nilai-nilai Pancasila, terlibat aktif dalam pengambilan keputusan bersama, serta menunjukkan tanggung jawab dan komitmen terhadap tugas dan perannya.',
                'UUD NRI Tahun 1945' => 'Peserta didik mampu menganalisis dan mengevaluasi peraturan serta norma dalam kehidupan berbangsa dan bernegara serta mempraktikkan sikap sesuai aturan.',
                'Bhinneka Tunggal Ika' => 'Peserta didik mampu meneladani nilai-nilai Bhinneka Tunggal Ika dan mempraktikkan sikap kerja sama dalam keberagaman di lingkungan keluarga, sekolah, masyarakat, bangsa, dan negara.',
                'NKRI' => 'Peserta didik mampu menghargai dan menghormati jasa para pahlawan serta menunjukkan rasa cinta tanah air melalui perbuatan nyata dalam kehidupan sehari-hari.',
            ],
        ],
        'BIN' => [
            'A' => [
                'Menyimak' => 'Peserta didik mampu mengidentifikasi dan menangkap makna serta informasi dari teks sederhana yang dibacakan (dongeng, cerita, dan laporan percobaan sederhana) dan menceritakan kembali dengan bahasa sendiri.',
                'Membaca dan Memirsa' => 'Peserta didik mampu membaca dan memahami isi teks informatif dan fiksi sederhana, membaca nyaring dengan lafal dan intonasi yang tepat, serta memahami kata dan ungkapan baru.',
                'Berbicara dan Mempresentasikan' => 'Peserta didik mampu berbicara dengan pilihan kata dan sikap tubuh yang santun, memberikan respons terhadap pertanyaan, dan menceritakan pengalaman dengan runtut.',
                'Menulis' => 'Peserta didik mampu menulis kata dengan pola ejaan sederhana dan menyusun kalimat serta teks sederhana dengan tulisan tangan maupun tik, termasuk menulis ulang informasi dari teks yang dibaca.',
            ],
            'B' => [
                'Menyimak' => 'Peserta didik mampu menangkap makna dan informasi dari teks narasi, laporan, dan prosedur yang dibacakan, mengajukan pertanyaan, serta menyampaikan tanggapan atas teks yang disimak.',
                'Membaca dan Memirsa' => 'Peserta didik mampu memahami dan menginterpretasi informasi dari teks informatif dan fiksi, menemukan informasi penting dan rinci, serta menceritakan kembali isi teks dengan bahasa sendiri.',
                'Berbicara dan Mempresentasikan' => 'Peserta didik mampu menyampaikan pendapat dan tanggapan secara runtut dan santun, berpartisipasi dalam percakapan dan diskusi kelompok, serta mempresentasikan hasil kerja sederhana.',
                'Menulis' => 'Peserta didik mampu menulis teks narasi, laporan, dan prosedur sederhana dengan struktur yang jelas, menggunakan ejaan dan tanda baca yang tepat.',
            ],
            'C' => [
                'Menyimak' => 'Peserta didik mampu memahami dan mengevaluasi informasi dari teks yang disimak, membandingkan informasi dari berbagai sumber, serta menyampaikan penilaian terhadap isi dan bahasa teks.',
                'Membaca dan Memirsa' => 'Peserta didik mampu memahami, menginterpretasi, dan mengevaluasi teks informatif, persuasif, dan sastra; menemukan gagasan pokok dan informasi tersirat; serta menarik kesimpulan.',
                'Berbicara dan Mempresentasikan' => 'Peserta didik mampu menyampaikan gagasan, pendapat, dan presentasi secara efektif, runtut, dan santun dalam diskusi, dengan memperhatikan audiens dan tujuan komunikasi.',
                'Menulis' => 'Peserta didik mampu menulis berbagai jenis teks (narasi, deskripsi, laporan, persuasi) dengan struktur dan kosakata yang tepat, serta merevisi tulisan berdasarkan umpan balik.',
            ],
        ],
        'MTK' => [
            'A' => [
                'Bilangan' => 'Peserta didik mampu menunjukkan pemahaman urutan bilangan asli dan nilai tempat, menyusun dan membandingkan bilangan, serta memahami hubungan pecahan sederhana dengan kesatuan dan pecahan senilai.',
                'Aljabar' => 'Peserta didik mampu mengenali, membuat, melanjutkan, dan memprediksi pola pertambahan dan pengurangan sederhana serta memahami hubungan antara penjumlahan dan pengurangan.',
                'Pengukuran' => 'Peserta didik mampu membandingkan dan mengurutkan objek berdasarkan panjang, berat, dan kapasitas, serta menggunakan satuan baku dan alat ukur sederhana.',
                'Geometri' => 'Peserta didik mampu memahami relasi spasial dan memvisualisasikan bentuk dua dimensi dan tiga dimensi, termasuk menggambar bangun datar sederhana.',
                'Analisis Data dan Peluang' => 'Peserta didik mampu mengumpulkan, membaca, dan menafsirkan data dari berbagai sumber serta menyajikannya dalam bentuk tabel dan pictogram sederhana.',
            ],
            'B' => [
                'Bilangan' => 'Peserta didik mampu memahami operasi hitung bilangan asli (penjumlahan, pengurangan, perkalian, pembagian), pecahan berbeda penyebut, desimal, serta menerapkannya dalam masalah kontekstual.',
                'Aljabar' => 'Peserta didik mampu mengenali dan memperluas pola bilangan, memahami hubungan antaroperasi aritmetika, serta menyelesaikan masalah yang melibatkan operasi campuran.',
                'Pengukuran' => 'Peserta didik mampu mengukur panjang, berat, dan waktu dengan satuan baku, menyelesaikan masalah terkait uang, serta memahami keliling dan luas bangun datar.',
                'Geometri' => 'Peserta didik mampu mengidentifikasi, mengklasifikasi, dan menggambar bangun datar dan ruang, memahami sifat-sifatnya, serta menggunakan peta sederhana.',
                'Analisis Data dan Peluang' => 'Peserta didik mampu mengumpulkan, menyajikan, dan menafsirkan data dalam bentuk diagram batang serta memahami kejadian yang mungkin dan tidak mungkin.',
            ],
            'C' => [
                'Bilangan' => 'Peserta didik mampu memahami bilangan bulat, pecahan, desimal, persen, serta operasi hitung campurannya dan menerapkannya dalam penyelesaian masalah kontekstual.',
                'Aljabar' => 'Peserta didik mampu memahami pola bilangan dan hubungan fungsional sederhana, menggunakan variabel, serta menyelesaikan persamaan dan pertidaksamaan linear sederhana.',
                'Pengukuran' => 'Peserta didik mampu menyelesaikan masalah terkait luas permukaan, volume bangun ruang, kecepatan, debit, dan skala dengan satuan baku.',
                'Geometri' => 'Peserta didik mampu menganalisis sifat bangun datar dan ruang, memahami kekongruenan dan kesebangunan, serta menyelesaikan masalah yang melibatkan garis dan sudut.',
                'Analisis Data dan Peluang' => 'Peserta didik mampu mengumpulkan, menyajikan, menafsirkan, dan menganalisis data (diagram, rata-rata, median, modus) serta memahami peluang kejadian sederhana.',
            ],
        ],
        'IPA' => [
            'A' => [
                'Pemahaman IPAS' => 'Peserta didik mampu mengamati dan menceritakan ciri makhluk hidup dan benda di sekitarnya, bagian tubuh hewan dan tumbuhan, serta mengenal diri, keluarga, dan lingkungan tempat tinggalnya.',
                'Keterampilan Proses' => 'Peserta didik mampu mengamati, bertanya, mencoba, menalar, dan mengomunikasikan apa yang ia temukan melalui kegiatan sederhana yang menyenangkan.',
            ],
            'B' => [
                'Pemahaman IPAS' => 'Peserta didik mampu memahami ciri dan kebutuhan makhluk hidup, siklus hidup, struktur tumbuhan dan hewan, gaya, energi, serta dinamika sosial dan ekonomi di lingkungan sekitar.',
                'Keterampilan Proses' => 'Peserta didik mampu melakukan percobaan sederhana, mencatat dan mengolah pengamatan, serta mengomunikasikan hasilnya secara lisan dan tulisan.',
            ],
            'C' => [
                'Pemahaman IPAS' => 'Peserta didik mampu memahami sistem organ tubuh manusia, ekosistem, perubahan wujud benda, energi listrik, serta kondisi geografis dan potensi sosial-ekonomi Indonesia.',
                'Keterampilan Proses' => 'Peserta didik mampu merancang dan melakukan penyelidikan sederhana, menganalisis data, menarik kesimpulan, serta mengomunikasikan dan merefleksikan hasil penyelidikannya.',
            ],
        ],
        'PAI' => [
            'A' => [
                "Al-Qur'an Hadis" => 'Peserta didik mampu membaca dan menghafal surah pendek serta memahami arti dan maknanya dalam kehidupan sehari-hari.',
                'Akidah Akhlak' => 'Peserta didik mampu mengenal rukun iman, mengenal sifat wajib Allah, serta meneladani akhlak terpuji Rasulullah saw. dalam pergaulan sehari-hari.',
                'Fikih' => 'Peserta didik mampu mengenal dan mempraktikkan tata cara wudhu dan shalat serta mengenal perilaku yang dibolehkan dan dilarang dalam agama.',
                'Sejarah Peradaban Islam' => 'Peserta didik mampu mengenal kisah para nabi dan rasul sebagai teladan dalam kehidupan.',
            ],
            'B' => [
                "Al-Qur'an Hadis" => 'Peserta didik mampu membaca dan menghafal surah pilihan serta memahami kandungan hadis pilihan dan penerapannya.',
                'Akidah Akhlak' => 'Peserta didik mampu memahami rukun Islam, makna syirik, serta mempraktikkan akhlak terpuji kepada sesama dan lingkungan.',
                'Fikih' => 'Peserta didik mampu memahami syarat dan rukun shalat, zakat, serta praktik ibadah sehari-hari sesuai ketentuan.',
                'Sejarah Peradaban Islam' => 'Peserta didik mampu menceritakan sejarah nabi dan sahabat serta meneladani perjuangan mereka.',
            ],
            'C' => [
                "Al-Qur'an Hadis" => 'Peserta didik mampu membaca dan menghafal surah serta hadis pilihan, memahami kandungannya, dan menerapkannya dalam kehidupan.',
                'Akidah Akhlak' => 'Peserta didik mampu memahami makna iman kepada Allah dan hari akhir, qada dan qadar, serta menjaga diri dari perilaku maksiat.',
                'Fikih' => 'Peserta didik mampu memahami hukum bacaan shalat, puasa, haji dan umrah serta mempraktikkannya dengan benar.',
                'Sejarah Peradaban Islam' => 'Peserta didik mampu memahami sejarah perkembangan Islam di Indonesia dan jasa para ulama penyebar agama.',
            ],
        ],
        'PJOK' => [
            'A' => [
                'Keterampilan Gerak' => 'Peserta didik mampu mempraktikkan variasi dan kombinasi pola gerak dasar lokomotor, non-lokomotor, dan manipulatif dalam permainan dan aktivitas gerak sederhana.',
                'Pengetahuan Gerak' => 'Peserta didik mampu memahami konsep tubuh, ruang, usaha, dan hubungannya dengan aktivitas gerak serta manfaat aktivitas jasmaniah.',
                'Pemanfaatan Gerak' => 'Peserta didik mampu menerapkan pola gerak dasar dalam permainan tradisional dan aktivitas kebugaran jasmani sederhana.',
                'Pengembangan Karakter' => 'Peserta didik mampu menampilkan perilaku jujur, disiplin, santun, kerja sama, dan percaya diri melalui aktivitas gerak.',
            ],
            'B' => [
                'Keterampilan Gerak' => 'Peserta didik mampu mempraktikkan hasil evaluasi dan teknik gerak spesifik dalam permainan olahraga, aktivitas senam, dan gerak berirama.',
                'Pengetahuan Gerak' => 'Peserta didik mampu memahami teknik dasar gerak spesifik permainan dan olahraga serta cara mengukur kebugaran jasmani.',
                'Pemanfaatan Gerak' => 'Peserta didik mampu menerapkan teknik gerak spesifik dalam berbagai aktivitas jasmani untuk menunjang kebugaran dan kesehatan.',
                'Pengembangan Karakter' => 'Peserta didik mampu menampilkan perilaku tanggung jawab, sportif, kerja sama, dan menghargai perbedaan melalui aktivitas gerak.',
            ],
            'C' => [
                'Keterampilan Gerak' => 'Peserta didik mampu mempraktikkan teknik dasar dan taktik sederhana dalam permainan dan olahraga, aktivitas senam, dan gerak berirama secara terampil.',
                'Pengetahuan Gerak' => 'Peserta didik mampu menganalisis teknik dan taktik permainan serta olahraga dan perannya dalam menjaga kebugaran jasmani.',
                'Pemanfaatan Gerak' => 'Peserta didik mampu merancang dan menerapkan program aktivitas jasmani sederhana untuk kebugaran dan kesehatan diri.',
                'Pengembangan Karakter' => 'Peserta didik mampu menampilkan perilaku mandiri, sportif, kerja sama, kepemimpinan, dan solusi atas persoalan melalui aktivitas gerak.',
            ],
        ],
        'SBDP' => [
            'A' => [
                'Seni Rupa' => 'Peserta didik mampu mengamati, mengeksplorasi, dan menciptakan karya seni rupa dua dimensi dan tiga dimensi dengan alat dan bahan yang ada di lingkungan sekitar.',
                'Seni Musik' => 'Peserta didik mampu menyanyikan lagu-lagu daerah dan nasional serta memainkan alat musik ritmis sederhana dengan irama yang tepat.',
                'Seni Tari' => 'Peserta didik mampu menirukan dan menampilkan gerak tari sederhana dengan iringan musik serta mengeksplorasi ide gerak dari peristiwa alam.',
                'Seni Teater' => 'Peserta didik mampu menampilkan cerita rakyat dan dongeng dengan bahasa tubuh dan ekspresi sederhana.',
            ],
            'B' => [
                'Seni Rupa' => 'Peserta didik mampu mengamati, menganalisis, dan menciptakan karya seni rupa dengan menerapkan unsur-unsur seni rupa dalam karya dua dan tiga dimensi.',
                'Seni Musik' => 'Peserta didik mampu menyanyikan lagu dengan notasi balok sederhana dan memainkan alat musik melodis dalam ansambel sederhana.',
                'Seni Tari' => 'Peserta didik mampu menampilkan tari tradisional sederhana dengan pola lantai dan iringan serta menciptakan gerak tari berbasis pengalaman.',
                'Seni Teater' => 'Peserta didik mampu menampilkan improvisasi cerita rakyat dengan pengembangan tokoh dan dialog sederhana.',
            ],
            'C' => [
                'Seni Rupa' => 'Peserta didik mampu menganalisis dan mengapresiasi karya seni rupa serta menciptakan karya dua dan tiga dimensi dengan teknik dan media yang beragam.',
                'Seni Musik' => 'Peserta didik mampu membaca dan mempraktikkan notasi balok, menyanyikan lagu dalam berbagai birama, dan menampilkan musik ansambel.',
                'Seni Tari' => 'Peserta didik mampu menganalisis dan menampilkan tari daerah dengan pemahaman makna gerak serta mengkreasi karya tari sederhana.',
                'Seni Teater' => 'Peserta didik mampu mementaskan naskah cerita dengan pengembangan tokoh, latar, dan konflik secara berkesinambungan.',
            ],
        ],
        'BIG' => [
            'A' => [
                'Menyimak dan Berbicara' => 'Peserta didik mampu memahami instruksi dan informasi sederhana dalam bahasa Inggris serta berpartisipasi dalam percakapan sehari-hari yang sangat dasar.',
                'Membaca dan Menulis' => 'Peserta didik mampu membaca kata, frasa, dan kalimat sangat sederhana serta menulis kata dan kalimat pendek dengan panduan.',
            ],
            'B' => [
                'Menyimak dan Berbicara' => 'Peserta didik mampu memahami teks lisan sederhana dalam bahasa Inggris dan berinteraksi secara sederhana dalam situasi sehari-hari.',
                'Membaca dan Menulis' => 'Peserta didik mampu memahami teks pendek sederhana dan menulis teks deskriptif serta prosedur pendek dengan kosakata sehari-hari.',
            ],
            'C' => [
                'Menyimak dan Berbicara' => 'Peserta didik mampu memahami instruksi, informasi, dan teks lisan dalam bahasa Inggris serta berpartisipasi dalam percakapan transaksional dan interpersonal.',
                'Membaca dan Menulis' => 'Peserta didik mampu memahami dan menanggapi teks fungsional dan fiksi pendek serta menulis teks sederhana dengan struktur yang runtut.',
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->data as $subjectCode => $fases) {
            $subject = Subject::query()->where('code', $subjectCode)->first();

            if ($subject === null) {
                continue;
            }

            foreach ($fases as $fase => $elemenList) {
                $seq = 0;

                foreach ($elemenList as $elemen => $description) {
                    $seq++;

                    LearningAchievement::query()->firstOrCreate(
                        [
                            'subject_id' => $subject->getKey(),
                            'fase' => $fase,
                            'code' => "{$fase}.".$seq,
                        ],
                        [
                            'elemen' => $elemen,
                            'description' => $description,
                            'is_active' => true,
                        ],
                    );
                }
            }
        }
    }
}
